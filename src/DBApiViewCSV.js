const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')

class DBApiViewCSV extends DBApiView {
  constructor (dbapi, def, options) {
    super(dbapi, def, options)
    this.exportContentType = 'text/csv'
    this.exportExtension = 'csv'
  }

  encode (value) {
    if (value === null) {
      return ''
    }

    if (('' + value).match(/[\t\n,;\"]/)) {
      value = value.replace(/\\/g, '\\\\')
      value = value.replace(/"/g, '\\"')
      return '"' + value + '"'
    }

    return value
  }

  getPath (entry, path) {
    path = path.split(/\//g)
    let result = entry

    path.forEach(p => {
      if ((result instanceof Object || Array.isArray(result)) && typeof result[p] !== 'undefined') {
        result = result[p]
      } else {
        result = null
      }
    })

    return result
  }

  show (dom, options={}, callback=null) {
    this.get((err, result) => {
      if (err) {
        if (callback) {
          callback(err)
          callback = null
        }
        return this.emit('show', {
          error: err
        })
      }

      let columns = this.def.columns
      let renderedResult = '\ufeff' // BOM
      renderedResult += columns.join(',') + '\n'

      result.forEach(entry => {
        renderedResult += columns.map(col => this.encode(this.getPath(entry, col))) . join(',') + '\n'
      })

      if (dom) {
        emptyElement(dom)
        dom.appendChild(document.createTextNode(renderedResult))
      }

      if (callback) {
        callback(null, renderedResult)
        callback = null
      }
      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

module.exports = DBApiViewCSV
