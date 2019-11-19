const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')

class DBApiViewCSV extends DBApiView {
  constructor (dbapi, def, options) {
    super(dbapi, def, options)
    this.exportContentType = 'text/csv'
    this.exportExtension = 'csv'
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
        renderedResult += columns.map(col => entry[col]) . join(',') + '\n'
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
