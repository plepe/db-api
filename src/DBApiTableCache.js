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
          action.action = 'nop'
          action.cacheIndex = i
        }
      }
    }
  }

  modifyResult (action, result) {
    if (action.action === 'select') {
      this.addToCache(action, result)
    }

    if (action.action === 'nop') {
      result = this.queryCache[action.cacheIndex][1].map(id => this.entryCache[id])
    }

    return result
  }

  addToCache (query, result) {
    let id_field = this.table.spec ? this.table.spec.id_field || 'id' : 'id'
    let ids = []

    for (var k in result) {
      this.entryCache[result[k][id_field]] = result[k]
      ids.push(result[k][id_field])
    }

    this.queryCache.push([ JSON.parse(JSON.stringify(query)), ids ])
  }
}

module.exports = DBApiTableCache
