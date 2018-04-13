const DBView = require('./DBView')

class DBViewTwig extends DBView {
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
        return callback(err)
      }

      let data = {}
      let ret = ''

      result.forEach(entry => {
        data.entry = entry
        ret += this.template.render(data)
      })

      callback(null, ret)
    })
  }
}

module.exports = DBViewTwig
