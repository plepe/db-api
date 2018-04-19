const eventEmitter = require('event-emitter')
const emptyElement = require('@f/empty-element')
let viewExtensions = {
  'Leaflet': require('./DBApiExtLeaflet')
}

class DBApiView {
  constructor (dbApi, def, options) {
    this.api = dbApi
    this.def = def
    this.options = options
    this.extensions = []
  }

  extend (type, def, options) {
    this.extensions.push(new viewExtensions[type](this, def, options))
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
        result: result && result.length ? result[0] : null
      })

      if (err) {
        return callback(err)
      }

      callback(null, result[0])
    })
  }

  show (dom, options={}) {
    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      let renderedResult = JSON.stringify(result)
      if (dom) {
        emptyElement(dom)
        dom.appendChild(document.createTextNode(renderedResult))
      }

      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

eventEmitter(DBApiView.prototype)

module.exports = DBApiView
