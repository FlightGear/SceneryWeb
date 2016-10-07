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
      text: "SELECT ob_id, ob_text, ob_model, ST_Y(wkb_geometry) AS ob_lat, ST_X(wkb_geometry) AS ob_lon, \
           ob_heading, ob_gndelev, ob_elevoffset, ob_model, mo_shared, \
           concat('Objects/', fn_SceneDir(wkb_geometry), '/', fn_SceneSubDir(wkb_geometry), '/') AS obpath, ob_tile \
           FROM fgs_objects, fgs_models \
           WHERE ST_Within(wkb_geometry, ST_GeomFromText($1,4326)) \
           AND fgs_models.mo_id = fgs_objects.ob_model \
           LIMIT 400",
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

router.get('/stats/all', function(req, res, next) {

  Query({
      name: 'StatisticsAll',
      text: 'SELECT * from fgs_statistics ORDER BY st_date',
      values: []
  }, function(err, result) {
 
    if(err) return res.status(500).send("Database Error");
    var reply = { statistics: [] };
    result.rows.forEach( function(row) {
      reply.statistics.push( {
        'date' : row.st_date,
        'objects': row.st_objects,
        'models':  row.st_models,
        'authors': row.st_authors,
        'signs': row.st_signs,
        'navaids': row.st_navaids,
      });
    });
    res.json(reply);
  });
});

router.get('/stats/models/byauthor', function(req, res, next) {

  Query({
      name: 'StatisticsModelsByAuthor',
      text: 'SELECT COUNT(mo_id) AS count, au_name,au_id FROM fgs_models, fgs_authors WHERE mo_author = au_id GROUP BY au_id ORDER BY count DESC',
      values: []
  }, function(err, result) {
    if(err) return res.status(500).send("Database Error");
    var reply = { modelsbyauthor: [] };
    result.rows.forEach( function(row) {
      reply.modelsbyauthor.push( {
        'author' : row.au_name.trim(),
        'author_id' : Number(row.au_id),
        'count': Number(row.count),
      });
    });
    res.json(reply);
  });
});

router.get('/stats/models/bycountry', function(req, res, next) {

  Query({
      name: 'StatisticsModelsByCountry',
      text: 'SELECT COUNT(ob_id) AS count, COUNT(ob_id)/(SELECT shape_sqm/10000000000 FROM gadm2_meta WHERE iso ILIKE co_three) AS density, co_name, co_three FROM fgs_objects, fgs_countries WHERE ob_country = co_code AND co_three IS NOT NULL GROUP BY co_code HAVING COUNT(ob_id)/(SELECT shape_sqm FROM gadm2_meta WHERE iso ILIKE co_three) > 0 ORDER BY count DESC',
      values: []
  }, function(err, result) {
    if(err) return res.status(500).send("Database Error");
    var reply = { modelsbycountry: [] };

    result.rows.forEach( function(row) {
      reply.modelsbycountry.push( {
        'name' : row.co_name.trim(),
        'id' : row.co_three.trim(),
        'density': Number(row.density),
        'count': Number(row.count),
      });
    });
    res.json(reply);
  });
});

router.get('/models/list/:limit/:offset?', function(req, res, next) {

  var offset = Number(req.params.offset || 0);
  var limit = Number(req.params.limit||0);

  if( isNaN(offset) || isNaN(limit) ) {
      return res.status(500).send("Invalid Request");
  }

  limit = Math.min(10000,Math.max(1,limit));

  Query({
      name: 'ModelsList',
      text: "select mo_id, mo_path, mo_name, mo_notes, mo_shared from fgs_models limit $1 offset $2",
      values: [ limit, offset ]
    }, function(err, result) {

    if(err) {
      return res.status(500).send("Database Error");
    }

    var j = [];
    result.rows.forEach(function(row){
      j.push({
        'id': row.mo_id,
        'filename': row.mo_path,
        'name': row.mo_name,
        'notes': row.mo_notes,
        'shared': row.mo_shared
      });
    });
    res.json(j);
  });
});

router.get('/model/:id/thumb', function(req, res, next) {
  var id = Number(req.params.id || 0);
  if( isNaN(id) ) {
      return res.status(500).send("Invalid Request");
  }
  
  Query({
      name: 'ModelsThumb',
      text: "select mo_thumbfile from fgs_models where mo_id = $1",
      values: [ id ]
    }, function(err, result) {

    if(err) {
      return res.status(500).send("Database Error");
    }

    if( 0 == result.rows.length ) {
      return res.status(404).send("model not found");
    }

    if( result.rows[0].mo_thumbfile == null ) 
      return res.status(404).send("no thumbfile");

    var buf = new Buffer(result.rows[0].mo_thumbfile, 'base64');
    res.writeHead(200, {'Content-Type': 'image/jpeg'});
    res.end(buf);
  });
});

router.get('/models/datatable', function(req, res, next) {
  var draw = toNumber(req.query.draw);
  var start = toNumber(req.query.start);
  var length = toNumber(req.query.length);

  req.query.search = req.query.search || {}
  var search = req.query.search.value || '';

  order = req.query.order || [{ column: '1', dir: 'asc' }];

  var order_cols = {
    '1': 'mo_id',
    '2': 'mo_name',
    '3': 'mo_path',
    '4': 'mo_notes',
    '5': 'mo_modified',
    '6': 'mo_shared',
  }
  order_col = order_cols[toNumber(order[0].column)] || 'mo_id';
  order_dir = order[0].dir === 'asc' ? 'ASC' : 'DESC';

  //TODO: need to construct prepared statements for each order/dir combination
  var queryArgs = search == '' ? 
    {
      name: 'ModelsListDatatable',
      text: "select mo_id, mo_path, mo_name, mo_notes, mo_modified, mo_shared from fgs_models order by mo_modified desc limit $1 offset $2",
      values: [ length, start ]
    } :
    {
      name: 'ModelsSearchDatatable',
      text: "select mo_id, mo_path, mo_name, mo_notes, mo_modified, mo_shared from fgs_models where mo_path like $3 or mo_name like $3 or mo_notes like $3 order by mo_modified desc limit $1 offset $2",
      values: [ length, start, "%" + search + "%" ]
    };

  Query(queryArgs, function(err, result) {
    if(err) return res.status(500).send("Database Error");

    var j = [];
    result.rows.forEach(function(row){
      j.push({
        'id': row.mo_id,
        'filename': row.mo_path,
        'name': row.mo_name,
        'notes': row.mo_notes,
        'shared': row.mo_shared,
        'modified': row.mo_modified,
      });
    });

    Query({
      name: 'CountModels',
      text: 'select count(*) from fgs_models',
    }, function(err,result) {
      if(err) return res.status(500).send("Database Error");

      var count = result.rows[0].count;

      res.json({
        'draw': draw,
        'recordsTotal': count,
        'recordsFiltered': search == '' ? count : j.length,
        'data': j,
      });
    });
  });
});

router.get('/modelgroup/:id?', function(req, res, next) {
  var QueryArgs = req.params.id ?
  {
      name: 'ModelGroupsRead',
      text: "select mg_id, mg_name from fgs_modelgroups where mg_id = $1",
      values: [ toNumber(req.params.id) ]
  } :
  {
      name: 'ModelGroupsReadAll',
      text: "select mg_id, mg_name from fgs_modelgroups order by mg_id",
  };
  Query(QueryArgs, function(err, result) {

    if(err) {
      return res.status(500).send("Database Error");
    }

    var j = [];
    result.rows.forEach(function(row){
      j.push({
        'id': row.mg_id,
        'name': row.mg_name,
      });
    });
    res.json(j);
  });
});

router.get('/models/search/:pattern', function(req, res, next) {

  Query({
      name: 'ModelsSearch',
      text: "select mo_id, mo_path, mo_name, mo_notes, mo_shared from fgs_models where mo_path like $1 or mo_name like $1 or mo_notes like $1",
      values: [ "%" + req.params.pattern + "%" ]
    }, function(err, result) {

    if(err) {
      return res.status(500).send("Database Error");
    }

    var j = [];
    result.rows.forEach(function(row){
      j.push({
        'id': row.mo_id,
        'filename': row.mo_path,
        'name': row.mo_name,
        'notes': row.mo_notes,
        'shared': row.mo_shared
      });
    });
    res.json(j);
  });
});

module.exports = router;
