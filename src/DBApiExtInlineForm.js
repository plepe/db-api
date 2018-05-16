const DBApiExt = require('./DBApiExt')
const modulekitFormUpdate = require('./modulekitFormUpdate')
const async = {
  each: require('async/each')
}

class DBApiExtInlineForm extends DBApiExt {
  constructor (view, options={}) {
    super(view, options)

    view.on('showEntry', ev => {
      let inlineFormDoms = ev.dom.getElementsByClassName('inlineForm')
      if (inlineFormDoms.length === 0) {
        return
      }

      let entry = ev.entry
      let tableId = ev.table

      async.each(inlineFormDoms, (el, done) => {
        let def = { type: 'text' }
        let fieldId = el.getAttribute('field')
        let entryId = el.getAttribute('entryId') || entry.id

        if (el.hasAttribute('options')) {
          let options = JSON.parse(el.getAttribute('options'))
          for (var k in options) {
            def[k] = options[k]
          }
        }

        let d = {}
        d[fieldId] = def
        modulekitFormUpdate(d, this.api, tableId, (err) => {
          if (err) {
            return done(err)
          }

          if (def.disabled) {
            return done(null)
          }

          let formId = 'inlineForm-' + entryId + '-' + fieldId
          let fieldForm = new form(formId, {}, def)
          fieldForm.set_data(el.getAttribute('value'))

          while (el.firstChild) {
            el.removeChild(el.firstChild)
          }

          fieldForm.show(el)

          fieldForm.onchange = () => {
            let data = {}
            data[fieldId] = fieldForm.get_data()

            this.view.emit('savestart', {
              form: fieldForm
            })

            this.api.exec([
              {
                action: 'update',
                table: tableId,
                id: entryId,
                update: data
              },
              {
                action: 'select',
                cache: false,
                table: tableId,
                id: entryId
              }
            ], (err, result) => {
              if (!err) {
                fieldForm.set_orig_data(result[1][0][fieldId])
              }

              this.view.emit('save', {
                form: fieldForm,
                error: err,
                result: result
              })
            })
          }
        })
      })
    })
  }
}

module.exports = DBApiExtInlineForm
