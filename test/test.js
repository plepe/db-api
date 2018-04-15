const fs = require('fs')
const jsdom = require('jsdom')
global.document = (new jsdom.JSDOM('')).window.document

const DBApi = require('../src/DBApi')

const conf = JSON.parse(fs.readFileSync('test-conf.json', 'utf8'));

const assert = require('assert')
const twig = require('twig')

const api = new DBApi(conf.url, conf.options)

describe('DBApi.do', function () {
  it('should return something', function (done) {
    api.do(
      [
        { table: 'test2' }
      ],
      function (err, result) {
        console.log(JSON.stringify(result, null, '  '))
        assert.equal(!!result, true)
        done(err)
      }
    )
  })
})

describe('DBApiView', () => {
  it('init', () => {
    let view = api.createView('Base')
  })

  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView('Base')
    let dom = document.createElement('div')
    let expected = '[{"a":2,"b":"bar","d":"b"},{"a":4,"b":"bla","d":"b"}]'
    view.set_query({ table: 'test1' })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.equal(ev.result, expected)
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.show(dom)
  })
})

describe('DBApiViewJSON', () => {
  it('init', () => {
    let view = api.createView('JSON')
  })

  it('show', (done) => {
    let expected = `[
    {
        "id": 1,
        "commentsCount": 2,
        "comments": [
            {
                "test2_id": 1,
                "id": 2,
                "text": "foobar"
            },
            {
                "test2_id": 1,
                "id": 4,
                "text": "foobar2"
            }
        ]
    }
]`
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView('JSON')
    let dom = document.createElement('div')
    view.set_query({ table: 'test2', query: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.equal(ev.result, expected)
      stepDone()
    })
    view.show(dom)
  })
})

describe('DBApiViewTwig', () => {
  it('init', () => {
    let view = api.createView('Twig', '', { twig })
  })

  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView('Twig', '{{ entry.id }}: {{ entry.commentsCount }}\n', { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2', query: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1: 2\n' ])
      let expected = '<div>1: 2\n</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })
})
