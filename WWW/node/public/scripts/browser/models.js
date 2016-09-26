require.config({
});     

require([
], function( ) {
/*
  $(window).resize(function() {
    $map = $("#contenttron");
    var offset = $map.offset();
    var h = $(window).height() - offset.top - offset.left*2;
    $map.outerHeight(h);
  });
  $(window).trigger('resize');
*/

  $.getJSON('/scenemodels/modelgroup/', function(modelgroups) {

    function getModelgroup(id) {
      for( var i = 0; i < modelgroups.length; i++ ) {
        if( modelgroups[i].id == id ) return modelgroups[i].name;
      }
      return id;
    }

    $('table').DataTable({
      paging: true,
      serverSide: true,
      order: [[ 5, 'desc' ]],
      ajax: {
        url: '/scenemodels/models/datatable/',
        dataSrc: 'data',
      },
      columns: [
        { data: 'id', searchable: false, orderable: false, render: function(data) { return "<img class='model-table-thumb' src='/scenemodels/model/" + data + "/thumb'>";} },
        { data: 'id', searchable: false },
        { data: 'name' },
        { data: 'filename' },
        { data: 'notes' },
        { data: 'modified', searchable: false },
        { data: 'shared', searchable: false, render: function(data) { return getModelgroup(data); } },
      ],
    });
  });

});

