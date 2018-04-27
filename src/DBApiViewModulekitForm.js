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

  show (dom, options={}) {
    let todoQuery = []
    let todoFun = []
    this._handleValuesQueries(this.def, todoQuery, todoFun)

    if (todoQuery.length === 0) {
      return this._show(dom, options)
    }

    this.api.do(todoQuery, (err, result) => {
      for (var i = 0; i < todoQuery.length; i++) {
        todoFun[i](result[i])
      }

      this._show(dom, options)
    })
  }

  _show (dom, options={}) {
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

      this.emit('show', {
        form: this.form,
        error: null
      })
    })
  }
}

module.exports = DBApiViewModulekitForm
