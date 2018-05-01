const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')

class DBApiViewJSON extends DBApiView {
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

      let renderedResult = JSON.stringify(result, null, '    ')
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
}

module.exports = DBApiViewJSON
