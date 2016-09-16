var express = require('express');
var router = express.Router();
var dns = require('dns');

/* GET users listing. */
router.get('/status/:id?', function(req, res, next) {
  res.setHeader('Content-Type', 'text/html; charset=utf-8');

  var dnsname = "terrasync.flightgear.org";

  dns.resolve(dnsname, "NAPTR", function(err,addresses) {
    if( err ) {
      console.log(err);
      res.render('error', {} );
      return;
    } 

    addresses = addresses || [];
    addresses.forEach( function(address,index) {
      var separator = address.regexp.charAt(0);
      var tokens = address.regexp.split(separator);
      address.url = tokens[2];
      address.index = index;
    });

    res.render('tsstatus', {
      title: "Terrasync Status",
      dns: addresses,
      domainname: dnsname,
    });

  });

});

module.exports = router;

