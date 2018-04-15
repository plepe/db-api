const DBApiView = require('./DBApiView')
const emptyElement = require('@f/empty-element')

class DBApiViewTwig extends DBApiView {
  constructor (dbApi, def, options) {
    super(dbApi, def, options)

    this.twig = this.options.twig
    this.template = this.twig.twig({
      data: def
    })
  }

  show (dom) {
    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      let data = {}
      let renderedResult = []

      emptyElement(dom)
      result.forEach(entry => {
        data.entry = entry
        let r = this.template.render(data)
        let div = document.createElement('div')
        div.innerHTML = r
        dom.appendChild(div)
        renderedResult.push(r)
      })

      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

module.exports = DBApiViewTwig
