const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')

class DBApiViewJSON extends DBApiView {
  show (dom, options={}) {
    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      let renderedResult = JSON.stringify(result, null, '    ')
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

module.exports = DBApiViewJSON
