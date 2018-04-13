var url = 'http://localhost/~skunk/db-api/db.php'

var assert = require('assert')
var DBApi = require('../src/DBApi')
var api = new DBApi(url)

const fs = require('fs')
const conf = JSON.parse(fs.readFileSync('test-conf.json', 'utf8'));

const assert = require('assert')
const twig = require('twig')

const DBView = require('../src/DBView')
const DBViewJSON = require('../src/DBViewJSON')
const DBViewTwig = require('../src/DBViewTwig')

const dbApi = new DBApi(conf.url, conf.options)

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
  }
}

describe('DBView', () => {
  it('init', () => {
    new DBView(dbApi)
  })

  it('show', (done) => {
    let view = new DBView(dbApi)
    view.set_query({ table: 'test1' })
    view.show((err, result) => {
      assert.equal(err, null, 'Error should be null')
      assert.equal(result, '[{"a":2,"b":"bar","d":"b"},{"a":3,"b":"bla","d":"b"}]')
      done()
    })
  })
})

describe('DBViewJSON', () => {
  it('init', () => {
    new DBViewJSON(dbApi)
  })

  it('show', (done) => {
    let view = new DBViewJSON(dbApi)
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

describe('DBViewTwig', () => {
  it('init', () => {
    new DBViewTwig(dbApi, '', { twig })
  })

  it('show', (done) => {
    let view = new DBViewTwig(dbApi, '{{ entry.id }}: {{ entry.commentsCount }}\n', { twig })
    view.set_query({ table: 'test2', query: 1 })
    view.show((err, result) => {
      assert.equal(err, null, 'Error should be null')
      let expected = '1: 2\n'
      assert.equal(result, expected)
      done()
    })
  })
})
