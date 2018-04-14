const fs = require('fs')

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
    let view = api.createView('Base')
    view.set_query({ table: 'test1' })
    view.show((err, result) => {
      assert.equal(err, null, 'Error should be null')
      assert.equal(result, '[{"a":2,"b":"bar","d":"b"},{"a":4,"b":"bla","d":"b"}]')
      done()
    })
  })
})

describe('DBApiViewJSON', () => {
  it('init', () => {
    let view = api.createView('JSON')
  })

  it('show', (done) => {
    let view = api.createView('JSON')
    view.set_query({ table: 'test2', query: 1 })
    view.show((err, result) => {
      assert.equal(err, null, 'Error should be null')
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
      assert.equal(result, expected)
      done()
    })
  })
})

describe('DBApiViewTwig', () => {
  it('init', () => {
    let view = api.createView('Twig', '', { twig })
  })

  it('show', (done) => {
    let view = api.createView('Twig', '{{ entry.id }}: {{ entry.commentsCount }}\n', { twig })
    view.set_query({ table: 'test2', query: 1 })
    view.show((err, result) => {
      assert.equal(err, null, 'Error should be null')
      let expected = '1: 2\n'
      assert.equal(result, expected)
      done()
    })
  })
})
