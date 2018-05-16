const DBApiView = require('./DBApiView')
const modulekitFormUpdate = require('./modulekitFormUpdate')

class DBApiViewModulekitForm extends DBApiView {
  show (dom, options={}, callback=null) {
    let table = this.api.getTable(this.query.table)
    modulekitFormUpdate(this.def.def, this.api, this.query.table, (err, result) => {
      this._show(dom, options, table, callback)
    })
  }

  _show (dom, options, table, callback) {
    this.query.old_id = true

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

      let domForm = document.createElement('form')
      dom.appendChild(domForm)

      this.def.def[table.old_id_field || '__id'] = {
        type: 'hidden'
      }

      let options = {
        type: 'array',
        default: 1
      }
      let formDef = { def: this.def.def, type: 'form' }

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

        this.emit('savestart', {
          form: this.form
        })

        changeset.push({
          action: 'insert-update',
          table: this.query.table,
          data: data
        })

        let query = JSON.parse(JSON.stringify(this.query))
        query.cache = false
        changeset = changeset.concat(query)

        this.api.exec(changeset, (err, result) => {
          if (!err) {
            this.form.set_orig_data(result[1])
            this.form.set_data(result[1])
          }

          this.emit('save', {
            form: this.form,
            error: err,
            result: result
          })
        })

        return false
      }

      if (callback) {
        callback(null)
        callback = null
      }
      this.emit('show', {
        form: this.form,
        entries: result,
        error: null
      })
    })
  }
}

module.exports = DBApiViewModulekitForm
