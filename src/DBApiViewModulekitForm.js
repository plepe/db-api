const DBApiView = require('./DBApiView')

class DBApiViewModulekitForm extends DBApiView {
  show (div, callback) {
    this.get((err, result) => {
      if (err) {
        this.emit('show', {
          error: err
        })
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

      this.emit('show', {
        form: this.form,
        error: null
      })
    })
  }
}

module.exports = DBApiViewModulekitForm
