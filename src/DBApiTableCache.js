const deepEqual = require('deep-equal')

class DBApiTableCache {
  constructor (table) {
    this.entryCache = {}
    this.queryCache = []
    this.table = table
  }

  get (id) {
    if (id in this.entryCache) {
      return this.entryCache[id]
    }

    return undefined
  }

  modifyAction (action) {
    if (!('action' in action)) {
      action.action = 'select'
    }

    if (action.action === 'select') {
      for (var i = 0 ; i < this.queryCache.length; i++) {
        if (deepEqual(action, this.queryCache[i][0])) {

          if (!('cache' in action) || action.cache !== false) {
            action.action = 'nop'
          }

          action.cacheIndex = i
          return
        }
      }

      this.queryCache.push([ JSON.parse(JSON.stringify(action)), null, [] ])
      action.cacheIndex = i
    }
  }

  modifyResult (action, result, callback) {
    if (action.action === 'select') {
      this.addToCache(action, result)
    }

    if (action.action === 'nop') {
      let ids = this.queryCache[action.cacheIndex][1]
      if (ids !== null) {
        result = ids.map(id => this.entryCache[id])
      }
      else {
        this.queryCache[action.cacheIndex][2].push(callback)
        return
      }
    }

    callback(null, result)
  }

  addToCache (action, result) {
    let id_field = this.table.schema ? this.table.schema.id_field || 'id' : 'id'
    let ids = []

    for (var k in result) {
      this.entryCache[result[k][id_field]] = result[k]
      ids.push(result[k][id_field])
    }

    if ('cacheIndex' in action) {
      this.queryCache[action.cacheIndex][1] = ids

      if (this.queryCache[action.cacheIndex][2].length) {
        let pending = this.queryCache[action.cacheIndex][2]
        this.queryCache[action.cacheIndex][2] = null
        pending.forEach(callback => {
          callback(null, result)
        })
      }
    }
  }
}

module.exports = DBApiTableCache
