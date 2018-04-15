const DBApiView = require('./DBApiView')

class DBApiViewTwig extends DBApiView {
  constructor (dbApi, def, options) {
    super(dbApi, def, options)

    this.twig = this.options.twig
    this.template = this.twig.twig({
      data: def
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

      let data = {}
      let renderedResult = []

      result.forEach(entry => {
        data.entry = entry
        renderedResult.push(this.template.render(data))
      })

      callback(null, renderedResult.join(''))

      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

module.exports = DBApiViewTwig
