/* badges-tab.js */
(function($){

  $('#tabs').tabs();

  HandlebarsIntl.registerWith(Handlebars);
  var badgeTemplate = Handlebars.compile( $('#badge-template').html() );

  /**
   * Get the user's certification data from Sales Force
   */
  if( '' != wpvars.student_id ){
    var studentDataURL = wpvars.jsonurl + 'getStudentData/?student_id=' + wpvars.student_id;
    console.log('studentDataURL: ' + studentDataURL);
    $.ajax({
      url: studentDataURL,
      type: 'GET',
      dataType: 'json',
      success: function(response){
        console.log('Just called SF API...');
        console.log(response.instance_url);

        var badges = response.data.badges;

        for (var i = 0; i < badges.length; i++) {
          badges[i].element = '#badge-display';
          displayBadge( badges[i] );
        }
        $('#badges .alert').fadeOut(600);
        $('#badge-display').fadeIn(600);

        var certificates = response.data.certificates;
        for (var i = 0; i < certificates.length; i++ ){
          certificates[i].element = '#certificate-display';
          displayBadge( certificates[i] );
        }
      },
      error: function(response){
        console.log('There was an error retriving the Student Data.');
        console.log(response.responseJSON.message);
      },
      beforeSend: function(xhr){
        xhr.setRequestHeader('X-WP-Nonce',wpvars.nonce);
      }
    });
  } else {
    $('#badges .alert').fadeOut();
    $('#badge-display').fadeIn().html('<div class="alert alert-error"><strong>No Student Data Found</strong><br/>We were unable to retrieve any Student Data for your account email address (<em>' + wpvars.student_email + '</em>). Please contact B2T Training and alert them to this error. Be sure to mention this email address: <em>' + wpvars.student_email + '</em>.</div>');
  }

  /**
   * Add a badge to user's backpack
   *
   * Bakes the badge assertion into the badge graphic via the
   * OpenBadges issuer API.
   */
  $('#badge-display').on('click', '.add-to-backpack', function(e){
    badge = $(this).attr('data-badge');
    completed = $(this).attr('data-completed');
    url = wpvars.assertionurl + '?email=' + wpvars.student_email + '&badge=' + badge + '&completed=' + completed;
    //console.log(url);
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
      if ( 'Class' == criteria[i].type ) {
        classes.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: criteria[i].completed_date});
      } else if( 'Exam' == criteria[i].type ){
        exams.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: criteria[i].completed_date});
      }
    }

    badge.classes = classes;
    badge.exams = exams;

    $.getJSON( wpvars.criteriaurl, {name: badge.name}, function(data){
      if( typeof data.data.image !== 'undefined' ){
        badge.image = data.data.image;
      } else {
        badge.image = wpvars.default_badge;
      }

      switch(data.data.slug){
        case 'product-owner-practitioner':
          // Add `ah` to the slug to get it to sort
          // immediately after `agile-analysis-practitioner`
          badge.slug = 'ah-' + data.data.slug;
          break;

        default:
          badge.slug = data.data.slug;
      }


      $(badge.element).append( badgeTemplate( badge ) );
      sortBadges(badge.element);
    });
  }

  /**
   * Sorts badges alphabetically
   */
  var sortBadges = function(id){
    $('.badge-container', id).sort(function(a,b){
      return $(a).attr('id') > $(b).attr('id');
    }).appendTo(id);
  }
})(jQuery);