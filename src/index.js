const DBApi = require('db-api')
const DBViewTwig = require('./DBViewTwig')
const DBViewModulekitForm = require('./DBViewModulekitForm')
const twig = require('twig')

window.onload = () => {
  let api = new DBApi('api.php')

  let div1 = document.createElement('div')
  document.body.appendChild(div1)

  let view = new DBViewTwig(api, 'Entry {{ entry.id }}: {{ entry.commentsCount }} Comments\n<ul>{% for c in entry.comments %}<li>{{ c.text }}</li>{% endfor %}</ul>', { twig })
  view.set_query({ table: 'test2' })
  view.show((err, result) => {
    if (err) {
      global.alert(err)
    }
    div1.innerHTML = result
  })

  let div2 = document.createElement('div')
  document.body.appendChild(div2)
  let view2 = new DBViewModulekitForm(api,
    {
      id: {
        type: 'integer',
        name: 'ID'
      },
      visible: {
        type: 'boolean',
        name: 'Visible'
      },
      comments: {
        type: 'array',
        count: { 'default': 1 },
        def: {
          type: 'form',
          def: {
            id: {
              type: 'integer',
              name: 'ID'
            },
            text: {
              type: 'textarea',
              name: 'Text'
            }
          }
        }
      }
    }
  )
  view2.set_query({ table: 'test2' })
  view2.show(div2, (err) => {
    if (err) {
      global.alert(err)
    }
  })
}
