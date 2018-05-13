const eventEmitter = require('event-emitter')
const emptyElement = require('@f/empty-element')
const async = {
  each: require('async/each')
}
let viewExtensions = {
  'Dummy': require('./DBApiExtDummy'),
  'Leaflet': require('./DBApiExtLeaflet'),
  'ModulekitFormLeaflet': require('./DBApiExtModulekitFormLeaflet'),
  'InlineForm': require('./DBApiExtInlineForm'),
  'ImageExport': require('./DBApiExtImageExport')
}

class DBApiView {
  constructor (dbApi, def, options) {
    this.api = dbApi
    this.def = def
    this.options = options
    this.extensions = []

    if (this.def.extensions) {
      for (var i in this.def.extensions) {
        this.extend(this.def.extensions[i], options)
      }
    }
  }

  extend (def, options) {
    this.extensions.push(new viewExtensions[def.type](this, def, options))
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

  show (dom, options={}, callback=null) {
    this.get((err, result) => {
      if (err) {
        if (callback) {
          callback(err, null)
          callback = null
        }
        return this.emit('show', {
          error: err
        })
      }

      let renderedResult = JSON.stringify(result)
      if (dom) {
        emptyElement(dom)
        dom.appendChild(document.createTextNode(renderedResult))
      }

      if (callback) {
        callback(null)
        callback = null
      }
      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }

  export (options, callback) {
    let div = document.createElement('div')
    div.style = {
      position: 'absolute',
      width: 0,
      height: 0
    }
    document.body.appendChild(div)

    options.step = 0
    this.show(div, options, (err) => {
      async.each(
        this.extensions,
        (ext, callback) => {
          if ('export' in ext) {
            ext.export(div, options, callback)
          } else {
            callback(null)
          }
        },
        (err) => {
          callback(null, div.innerHTML, 'text/html', 'html')
          document.body.removeChild(div)
        }
      )
    })
  }
}

eventEmitter(DBApiView.prototype)

module.exports = DBApiView
