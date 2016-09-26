var express = require('express');
var passport = require('passport');
var DB = require('../config/database.js');
var router = express.Router();

/* GET home page. */
router.get('/', function(req, res, next) {
  res.render('index', { title: 'FlightGear Aviation Resources' } );
});

router.get('/profile', isLoggedIn, function(req, res, next) {
console.log("profile!", req.user);
  res.render('profile', { title: 'FlightGear Aviation Resources - Profile', user: req.user } );
});

router.get('/logout', function(req, res) {
  req.logout();
  res.redirect('/');
});

router.get('/:page', function(req, res, next) {
  res.render(req.params.page, { title: 'FlightGear Aviation Resources', user: req.user } );
});

router.get('/browse/models', function(req, res, next) {
  res.render('model-browser' );
});

function login(req,res,next)
{
  var authargs = {};
  if( req.params.method == 'google' ) { 
    authargs.scope = ['profile', 'email'];
  }

  if( req.user ) {
console.log("authen", req.params.method, req.user, req.account );
    passport.authenticate(req.params.method,authargs)(req,res,next);
  } else {
console.log("author", req.params.method, req.user, req.account );
    passport.authorize(req.params.method,authargs)(req,res,next);
  }
}

function loginCallback(req,res,next)
{
console.log("callback", req.user, req.account );
  passport.authenticate(req.params.method, {
    successRedirect: '/profile',
    failureRedirect: '/'
  })(req, res, next);
}

router.get('/auth/sourceforge', passport.authenticate('oauth') );
router.get('/auth/sourceforge/callback', passport.authenticate('oauth', { 
  failureRedirect: '/login' }),
  function(req, res) {
console.log("yay");
    // Successful authentication, redirect home.
    res.redirect('/');
  } );

router.get('/auth/:method', login );

// the callback after google has authenticated the user
router.get('/auth/:method/callback', loginCallback );

function isLoggedIn(req, res, next) {

    // if user is authenticated in the session, carry on 
    if (req.isAuthenticated())
        return next();

    // if they aren't redirect them to the home page
    res.redirect('/');
}

module.exports = router;

