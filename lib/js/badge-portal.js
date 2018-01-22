/* badge-portal.js */
(function($){

  var badgeTemplate = Handlebars.compile( $('#badge-template').html() );

  /**
   * Get the user's certification data from Sales Force
   */
  $.getJSON( wpvars.jsonurl, function(data){
    console.log('Salesforce data:');
    console.log(data);

    var badges = data.badges;

    for (var i = 0; i < badges.length; i++) {
      displayBadge( badges[i] );
    }
  });

  /**
   * Add a badge to user's backpack
   *
   * Bakes the badge assertion into the badge graphic via the
   * OpenBadges issuer API.
   */
  $('#badge-display').on('click', '.add-to-backpack', function(e){
    badge = $(this).attr('data-badge');
    completed = $(this).attr('data-completed');
    url = wpvars.assertionurl + '?email=' + wpvars.user_email + '&badge=' + badge + '&completed=' + completed;
    console.log(url);
    OpenBadges.issue([url], function(errors,successes){
      console.log('OpenBadges.issue errors:');
      console.log(errors);
      console.log('OpenBadges.issue successes:');
      console.log(successes);
    });
    e.preventDefault();
  });

  /**
   * Displays a badge graphic, HTML criteria, and "Add to Backpack" button
   *
   * @param      object  badge   The badge
   */
  var displayBadge = function( badge ){
    var criteria = badge.criteria;
    var classes = [];
    var exams = [];

    for( var i = 0; i < criteria.length; i++ ){
      if ( 'class' == criteria[i].type ) {
        classes.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: criteria[i].completed_date});
      } else if( 'exam' == criteria[i].type ){
        exams.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: criteria[i].completed_date});
      }
    }

    badge.classes = classes;
    badge.exams = exams;

    console.log(badge);

    $.getJSON( wpvars.criteriaurl, {name: badge.name}, function(data){

      /*
      if( typeof data.data.criteria !== 'undefined' ){
        badge.criteriaHtml = data.data.criteria;
      } else {
        badge.criteriaHtml = '<p>Error: No criteria defined for this badge!</p>';
      }
      */

      if( typeof data.data.image !== 'undefined' ){
        badge.image = data.data.image;
      }

      badge.slug = data.data.slug;

      $('#badge-display').append( badgeTemplate( badge ) );
      sortBadges();
    });
  }

  /**
   * Sorts badges alphabetically
   */
  var sortBadges = function(){
    $('.badge-container', '#badge-display').sort(function(a,b){
      return $(a).attr('id') > $(b).attr('id');
    }).appendTo('#badge-display');
  }
})(jQuery);