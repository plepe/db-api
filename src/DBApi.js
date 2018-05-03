const httpGetJSON = require('./httpGetJSON')
const DBApiTable = require('./DBApiTable')

let viewTypes = {
  'Base': require('./DBApiView'),
  'JSON': require('./DBApiViewJSON'),
  'Twig': require('./DBApiViewTwig'),
  'ModulekitForm': require('./DBApiViewModulekitForm')
}

class DBApi {
  constructor (url, options, callback) {
    this.url = url
    this.options = options
    this.tables = {}

    this.do(
      [{
        action: 'schema'
      }],
      (err, result) => {
        if (err) {
          return callback(err)
        }

        for (let i in result[0]) {
          this.tables[result[0][i].id] = new DBApiTable(result[0][i])
        }

        callback(null)
      }
    )
  }

  getTable (id) {
    return this.tables[id]
  }

  do (actions, callback) {
    httpGetJSON(
      'POST',
      this.url,
      JSON.stringify(actions),
      (err, result) => {
        if (err) {
          return callback(err, null)
        }

        if ('error' in result) {
          return callback(result.error, null)
        }

        return callback(null, result)
      }
    )
  }

  getCached (tableId, id, callback) {
    let cache = this.tables[tableId].cache
    let result = cache.get(id)

    if (typeof result !== 'undefined') {
      return result
    }

    if (!this.toLoad) {
      this.toLoad = {}
      this.toLoadCallbacks = []
    }
    if (!this.toLoad[tableId]) {
      this.toLoad[tableId] = {}
    }
    this.toLoad[tableId][id] = true
    if (callback) {
      this.toLoadCallbacks.push(callback)
    }

    if (!this.toLoadTimer) {
      this.toLoadTimer = global.setTimeout(this.loadCache.bind(this), 0)
    }
  }

  loadCache () {
    let query = []
    for (let tableId in this.toLoad) {
      let table = this.tables[tableId]
      let id_field = table.spec ? table.spec.id_field || 'id' : 'id'

      query.push({
        table: tableId,
        query: [[ id_field, 'in', Object.keys(this.toLoad[tableId]) ]]
      })
    }

    var callbacks = this.toLoadCallbacks
    var loading = this.toLoad
    this.do(query, (err, result) => {
      if (err) {
        return callbacks.forEach(callback => callback(err))
      }

      let i = 0
      for (let tableId in loading) {
        let table = this.tables[tableId]

        table.cache.addToCache(result[i])
        i++
      }

      callbacks.forEach(callback => callback())
    })

    delete this.toLoad
    delete this.toLoadCallbacks
  }

  clearCache () {
    this.cache = {}
  }

  createView (def, options) {
    if (!(def.type in viewTypes)) {
      throw new Error('db-api view type ' + def.type + ' not defined!')
    }

    return new viewTypes[def.type](this, def, options)
  }
}

module.exports = DBApi
