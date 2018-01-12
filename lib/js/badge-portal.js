/* badge-portal.js */
(function($){
  console.log('badge-portal.js is loaded.');
  var badgeTemplate = Handlebars.compile( $('#badge-template').html() );

  $.getJSON( wpvars.jsonurl + 'jabbott@sfgmembers.com', function(data){
    console.log('Salesforce data:');
    console.log(data);

    var badges = data.badges;

    for (var i = 0; i < badges.length; i++) {
      displayBadge( badges[i] );
    }
  });

  $('#badge-display').on('click', '.add-to-backpack', function(e){
    badge = $(this).attr('data-badge');
    completed = $(this).attr('data-completed');
    url = wpvars.assertionurl + '?email=' + wpvars.user_email + '&badge=' + badge + '&completed=' + completed;
    console.log(url);
    OpenBadges.issue([url], function(errors,successes){
      console.log(errors);
      console.log(successes)
    });
    e.preventDefault();
  });

  var displayBadge = function( badge ){
    $.getJSON( wpvars.criteriaurl, {name: badge.name}, function(data){

      if( typeof data.data.criteria !== 'undefined' ){
        badge.criteriaHtml = data.data.criteria;
      } else {
        badge.criteriaHtml = '<p>Error: No criteria defined for this badge!</p>';
      }

      if( typeof data.data.image !== 'undefined' ){
        badge.image = data.data.image;
      }

      badge.slug = data.data.slug;

      $('#badge-display').append( badgeTemplate( badge ) );
      sortBadges();
    });
  }

  var sortBadges = function(){
    $('.badge-container', '#badge-display').sort(function(a,b){
      return $(a).attr('id') > $(b).attr('id');
    }).appendTo('#badge-display');
  }
})(jQuery);