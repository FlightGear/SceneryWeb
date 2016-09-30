var express = require('express');
var pg = require('pg');

var router = express.Router();

var client = new pg.Client();

if (!String.format) {
  String.format = function(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number] 
        : match
      ;
    });
  };
}

function toNumber(x) {
  var n = Number(x||0);
  return isNaN(n) ? 0 : n;
}


var selectModelsWithinSql = 
   "SELECT ob_id, ob_text, ob_model, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, \
           ob_heading, ob_gndelev, ob_elevoffset, ob_model, mo_shared, \
           concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath, ob_tile \
           FROM fgs_objects, fgs_models \
           WHERE ST_Within(wkb_geometry, ST_GeomFromText($1,4326)) \
           AND fgs_models.mo_id = fgs_objects.ob_model \
           LIMIT 400";

var selectSignsWithinSql = 
   "SELECT si_id, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, \
           si_heading, si_gndelev, si_definition \
           FROM fgs_signs \
           WHERE ST_Within(wkb_geometry, ST_GeomFromText($1,4326)) \
           LIMIT 400";

var selectNavaidsWithinSql = 
   "SELECT na_id, ST_Y(na_position) AS na_lat, ST_X(na_position) AS na_lon, \
           na_type, na_elevation, na_frequency, na_range, na_multiuse, na_ident, na_name, na_airport_id, na_runway \
           FROM fgs_navaids \
           WHERE ST_Within(na_position, ST_GeomFromText($1,4326)) \
           LIMIT 400";

var pool = new pg.Pool({
  user: 'webuser', //env var: PGUSER 
  database: 'scenemodels', //env var: PGDATABASE 
//  password: 'secret', //env var: PGPASSWORD 
  port: 5432, //env var: PGPORT 
  max: 10, // max number of clients in the pool 
  idleTimeoutMillis: 30000, // how long a client is allowed to remain idle before being closed 
});

pool.on('error', function (err, client) {
  // if an error is encountered by a client while it sits idle in the pool 
  // the pool itself will emit an error event with both the error and 
  // the client which emitted the original error 
  // this is a rare occurrence but can happen if there is a network partition 
  // between your application and the database, the database restarts, etc. 
  // and so you might want to handle it and at least log it out 
  console.error('idle client error', err.message, err.stack)
})

function Query(options,cb) {

  pool.connect(function(err, client, done) {

    if(err) {
      console.error('error fetching client from pool', err);
      return cb(err);
    }

    client.query(options, function(err, result) {
      //call `done()` to release the client back to the pool 
      done();
 
      if(err) {
        console.error('error running query', err);
        return cb(err);
      }

      return cb(null,result);

    });
  });
}

 
router.get('/objects/', function(req, res, next) {

  var east = toNumber(req.query.e);
  var west = toNumber(req.query.w);
  var north = toNumber(req.query.n);
  var south = toNumber(req.query.s);

  Query({
      name: 'Select Models Within',
      text: selectModelsWithinSql, 
      values: [ String.format('POLYGON(({0} {1},{2} {3},{4} {5},{6} {7},{0} {1}))',west,south,west,north,east,north,east,south) ]
    }, function(err, result) {
 
    if(err) {
      return res.status(500).send("Database Error");
    }

    var features = [];
    if( result.rows ) result.rows.forEach(function(row) {
      features.push({
        'type': 'Feature',
        'id': row['ob_id'],
        'geometry':{
          'type': 'Point','coordinates': [row['ob_lon'], row['ob_lat']]
        },
        'properties': {
          'id': row['ob_id'],
          'heading': row['ob_heading'],
          'title': row['ob_text'],
          'gndelev': row['ob_gndelev'],
          'elevoffset': row['ob_elevoffset'],
          'model_id': row['ob_model'],
          'shared': row['mo_shared'],
          'stg': row['obpath'] + row['ob_tile'] + '.stg',
        }
      });
    });

    res.json({ 
        'type': 'FeatureCollection', 
        'features': features
      });
    });
});

router.get('/signs/', function(req, res, next) {

  var east = toNumber(req.query.e);
  var west = toNumber(req.query.w);
  var north = toNumber(req.query.n);
  var south = toNumber(req.query.s);

  Query({
      name: 'Select Signs Within',
      text: selectSignsWithinSql, 
      values: [ String.format('POLYGON(({0} {1},{2} {3},{4} {5},{6} {7},{0} {1}))',west,south,west,north,east,north,east,south) ]
    }, function(err, result) {
 
    if(err) {
      return res.status(500).send("Database Error");
    }

    var features = [];
    if( result.rows ) result.rows.forEach(function(row) {
      features.push({
        'type': 'Feature',
        'id': row['si_id'],
        'geometry':{
          'type': 'Point','coordinates': [row['ob_lon'], row['ob_lat']]
        },
        'properties': {
          'id': row['si_id'],
          'heading': row['si_heading'],
          'definition': row['si_definition'],
          'gndelev': row['si_gndelev'],
        }
      });
    });

    res.json({ 
      'type': 'FeatureCollection', 
      'features': features
    });
  });
});

router.get('/navaids/within/', function(req, res, next) {

  var east = toNumber(req.query.e);
  var west = toNumber(req.query.w);
  var north = toNumber(req.query.n);
  var south = toNumber(req.query.s);

  Query({
      name: 'Select Navaids Within',
      text: selectNavaidsWithinSql, 
      values: [ String.format('POLYGON(({0} {1},{2} {3},{4} {5},{6} {7},{0} {1}))',west,south,west,north,east,north,east,south) ]
    }, function(err, result) {
 
    if(err) {
      return res.status(500).send("Database Error");
    }

    var features = [];
    if( result.rows ) result.rows.forEach(function(row) {
      features.push({
        'type': 'Feature',
        'id': row['si_id'],
        'geometry':{
          'type': 'Point','coordinates': [row['na_lon'], row['na_lat']]
        },
        'properties': {
          'id': row['na_id'],
          'type': row['na_type'],
          'elevation': row['na_elevation'],
          'frequency': row['na_frequency'],
          'range': row['na_range'],
          'multiuse': row['na_multiuse'],
          'ident': row['na_ident'],
          'name': row['na_name'],
          'airport': row['na_airport_id'],
          'runway': row['na_runway'],
        }
      });
    });

    res.json({ 
      'type': 'FeatureCollection', 
      'features': features
    });
  });
});

router.get('/stats/', function(req, res, next) {

  Query({
      name: 'Statistics ',
      text: "with t1 as (select count(*) objects from fgs_objects), t2 as (select count(*) models from fgs_models), t3 as (select count(*) authors from fgs_authors) select objects, models, authors from t1, t2, t3",
      values: []
    }, function(err, result) {
 
    if(err) {
      return res.status(500).send("Database Error");
    }

    console.log(result.rows);
    var row = result.rows.length ? result.rows[0] : {};

    res.json({ 
      'stats': {
        'objects': row.objects || 0,
        'models':  row.models || 0,
        'authors': row.authors || 0,
      }
    });
  });
});

module.exports = router;
