const DBApiView = require('./DBApiView')

class DBApiViewModulekitForm extends DBApiView {
  show (dom, options={}) {
    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      let options = {
        type: 'array',
        default: 1
      }
      let formDef = { def: this.def, type: 'form' }

      this.form = new form(null, formDef, options)
      this.form.show(dom)
      this.form.set_data(result)

      this.emit('show', {
        form: this.form,
        error: null
      })
    })
  }
}

module.exports = DBApiViewModulekitForm
