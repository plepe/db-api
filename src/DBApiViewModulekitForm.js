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

      function updateSubTables (def, schema) {
        def.def.def[schema.old_id_field || '__id'] = {
          type: 'hidden'
        }

        def.removeable = !!schema.delete
        def.createable = !!schema.fields[schema.id_field || 'id'].write
        def.order = false

        for (var k in schema.fields) {
          if (schema.fields[k].type === 'sub_table' && k in def.def.def) {
            updateSubTables(def.def.def[k], schema.fields[k])
          }
        }
      }
      let x = { def: this.def }
      updateSubTables(x, table.schema)

      let options = {
        type: 'array',
        default: 1,
        order: x.order,
        removeable: x.removeable,
        createable: x.createable
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
