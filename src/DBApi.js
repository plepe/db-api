const httpGetJSON = require('./httpGetJSON')

let viewTypes = {
  'Base': require('./DBApiView'),
  'JSON': require('./DBApiViewJSON'),
  'Twig': require('./DBApiViewTwig'),
  'ModulekitForm': require('./DBApiViewModulekitForm')
}

class DBApi {
  constructor (url, options) {
    this.url = url
    this.options = options
    this.cache = {}
    this.tables = {}
  }

  addTable (spec) {
    this.tables[spec.id] = spec
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

  getCached (table, id, callback) {
    if (table in this.cache && id in this.cache[table]) {
      return this.cache[table][id]
    }

    if (!this.toLoad) {
      this.toLoad = {}
      this.toLoadCallbacks = []
    }
    if (!this.toLoad[table]) {
      this.toLoad[table] = {}
    }
    this.toLoad[table][id] = true
    if (callback) {
      this.toLoadCallbacks.push(callback)
    }

    if (!this.toLoadTimer) {
      this.toLoadTimer = global.setTimeout(this.loadCache.bind(this), 0)
    }
  }

  loadCache () {
    let query = []
    for (let table in this.toLoad) {
      let spec = this.tables[table]
      let id_field = spec ? spec.id_field || 'id' : 'id'

      query.push({
        table,
        query: [[ id_field, 'in', Object.keys(this.toLoad[table]) ]]
      })
    }

    var callbacks = this.toLoadCallbacks
    var loading = this.toLoad
    this.do(query, (err, result) => {
      if (err) {
        return callbacks.forEach(callback => callback(err))
      }

      let i = 0
      for (let table in loading) {
        let spec = this.tables[table]
        let id_field = spec ? spec.id_field || 'id' : 'id'

        if (!this.cache[table]) {
          this.cache[table] = {}
        }
        for (var k in result[i]) {
          this.cache[table][result[i][k][id_field]] = result[i][k]
        }
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

  createView (type, def, options) {
    if (!(type in viewTypes)) {
      throw new Error('db-api view type ' + type + ' not defined!')
    }

    return new viewTypes[type](this, def, options)
  }
}

module.exports = DBApi
