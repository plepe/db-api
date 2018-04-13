const DBView = require('./DBView')

class DBViewModulekitForm extends DBView {
  show (div, callback) {
    this.get((err, result) => {
      if (err) {
        return callback(err)
      }

      let options = {
        type: 'array',
        default: 1
      }
      let formDef = { def: this.def, type: 'form' }

      this.form = new form(null, formDef, options)
      this.form.show(div)
      this.form.set_data(result)

      callback(null)
    })
  }
}

module.exports = DBViewModulekitForm
