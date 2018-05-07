const httpGetJSON = require('./httpGetJSON')
const DBApiTable = require('./DBApiTable')
const async = {
  eachOf: require('async/eachOf')
}

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
    let allActionsAreNop = true
    for (let k in actions) {
      if ('table' in actions[k]) {
        this.tables[actions[k].table].cache.modifyAction(actions[k])

        if (actions[k].action !== 'nop') {
          allActionsAreNop = false
        }
      }
      else {
        allActionsAreNop = false
      }
    }

    if (allActionsAreNop) {
      let result = []
      for (let k in actions) {
        result.push(null)
      }

      return this._modifyResult(actions, result, callback)
    }

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

        this._modifyResult(actions, result, callback)
      }
    )
  }

  _modifyResult (actions, result, callback) {
    async.eachOf(actions,
      (action, k, done) => {
        if ('table' in actions[k]) {
          if (actions[k].action === 'select') {
            result[k].forEach((entry) => {
              this.tables[actions[k].table].updateFields(entry)
            })
          }

          this.tables[actions[k].table].cache.modifyResult(actions[k], result[k], (err, r) => {
            result[k] = r
            done(err)
          })
        } else {
          done()
        }
      },
      (err) => {
        callback(err, result)
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
      let id_field = table.schema ? table.schema.id_field || 'id' : 'id'

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

      callbacks.forEach(callback => callback())
    })

    delete this.toLoad
    delete this.toLoadCallbacks
  }

  clearCache () {
    for (let k in this.tables) {
      this.tables[k].clearCache()
    }
  }

  createView (def, options) {
    if (!(def.type in viewTypes)) {
      throw new Error('db-api view type ' + def.type + ' not defined!')
    }

    return new viewTypes[def.type](this, def, options)
  }
}

module.exports = DBApi
