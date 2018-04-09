const DBApiView = require('./DBApiView')

class DBApiViewModulekitForm extends DBApiView {
  show (dom, options={}) {
    this.get((err, result) => {
      if (err) {
        return this.emit('show', {
          error: err
        })
      }

      let domForm = document.createElement('form')
      dom.appendChild(domForm)

      let options = {
        type: 'array',
        default: 1
      }
      let formDef = { def: this.def, type: 'form' }

      this.form = new form(null, formDef, options)
      this.form.show(domForm)
      this.form.set_data(result)

      let input = document.createElement('input')
      input.type = 'submit'
      input.value = lang('save')
      domForm.appendChild(input)

      domForm.onsubmit = () => {
        let data = this.form.get_data()
        let changeset = []

        changeset.push({
          action: 'insert-update',
          table: this.query.table,
          data: data
        })

        this.api.do(changeset, (err, result) => {
          this.emit('save', {
            form: this.form,
            error: err,
            result: result
          })
        })

        return false
      }

      this.emit('show', {
        form: this.form,
        error: null
      })
    })
  }
}

module.exports = DBApiViewModulekitForm
