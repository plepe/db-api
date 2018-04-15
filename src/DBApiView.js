var eventEmitter = require('event-emitter')

class DBApiView {
  constructor (dbApi, def, options) {
    this.api = dbApi
    this.def = def
    this.options = options
  }

  set_query (query) {
    this.query = query
  }

  get (callback) {
    this.emit('loadstart', {
      query: this.query,
      start: 0
    })

    this.api.do([ this.query ], (err, result) => {
      this.emit('loadend', {
        query: this.query,
        error: err,
        result: result[0]
      })

      if (err) {
        return callback(err)
      }

      callback(null, result[0])
    })
  }

  show (callback) {
    this.get((err, result) => {
      if (err) {
        this.emit('show', {
          error: err
        })
        return callback(err)
      }

      let renderedResult = JSON.stringify(result)
      callback(null, renderedResult)

      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

eventEmitter(DBApiView.prototype)

module.exports = DBApiView
