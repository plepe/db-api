const DBApiView = require('./DBApiView')

class DBApiViewModulekitForm extends DBApiView {
  _handleValuesQueries (def, todoQuery, todoFun) {
    if (typeof def !== 'object') {
      return
    }

    if ('values_query' in def) {
      todoQuery.push(def.values_query)
      delete def.values_query
      todoFun.push(result => {
        def.values = result
      })
    } else {
      for (var k in def) {
        this._handleValuesQueries(def[k], todoQuery, todoFun)
      }
    }
  }

  show (dom, options={}, callback=null) {
    let todoQuery = []
    let todoFun = []

    if (!this.schema) {
      todoQuery.push({
        table: this.query.table,
        action: 'schema'
      })
      todoFun.push(result => this.schema = result[0])
    }

    this._handleValuesQueries(this.def, todoQuery, todoFun)

    if (todoQuery.length === 0) {
      return this._show(dom, options)
    }

    this.api.do(todoQuery, (err, result) => {
      for (var i = 0; i < todoQuery.length; i++) {
        todoFun[i](result[i])
      }

      checkFormRights(this.def.def, this.schema)

      this._show(dom, options, callback)
    })
  }

  _show (dom, options, callback) {
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

      this.def.def[this.schema.old_id_field || '__id'] = {
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

function checkFormRights (def, rights) {
  for (var k in def) {
    if (k === 'id') {
    }
    else if (!(k in rights.fields)) {
      delete def[k]
    }
    else if (rights.fields[k].type === 'sub_table') {
      checkFormRights(def[k].def.def, rights.fields[k])
    }
    else {
      def[k].may_read = ('read' in rights.fields[k] ? rights.fields[k].read : true)
      def[k].may_write = ('write' in rights.fields[k] ? rights.fields[k].write : false)

      if (rights.fields[k].write !== true) {
        def[k].type = 'label'
        def[k].include_data = false
      }
    }
  }
}

module.exports = DBApiViewModulekitForm
