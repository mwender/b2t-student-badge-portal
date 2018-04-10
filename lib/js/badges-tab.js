/* badges-tab.js */
(function($){

  $('#tabs').tabs();

  Handlebars.registerHelper('nl2br', function(options) {
    var nl2br = (options.fn(this) + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br>' + '$2');
    return new Handlebars.SafeString(nl2br);
  });

  HandlebarsIntl.registerWith(Handlebars);
  var badgeTemplate = Handlebars.compile( $('#badge-template').html() );
  var certificateTemplate = Handlebars.compile( $('#certificate-template').html() );

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
        var badges = response.data.badges;

        for (var i = 0; i < badges.length; i++) {
          badges[i].element = '#badge-display';
          displayBadge( badges[i] );
        }
        $('#badges .alert').fadeOut(600);
        $('#badge-display').fadeIn(600);

        var certificates = response.data.certificates;
        var displayLegacy = false;
        for (var i = 0; i < certificates.length; i++ ){
          switch(certificates[i].name){
            case 'Legacy BA Certified':
              if( certificates[i].completed )
                displayLegacy = true;
              break;

            default:
              // nothing
          }
        }
        for (var i = 0; i < certificates.length; i++ ){
          certificates[i].element = '#certificate-display';
          switch( certificates[i].name ){
            case 'Legacy BA Certified':
              if( displayLegacy )
                displayCertificate( certificates[i] );
              break;

            case 'BA Certified':
              if( ! displayLegacy )
                displayCertificate( certificates[i] );
              break;

            default:
              displayCertificate( certificates[i] );
          }

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
   * @param      {object}  badge   The badge
   */
  var displayBadge = function( badge ){
    var criteria = badge.criteria;
    var classes = [];
    var exams = [];

    for( var i = 0; i < criteria.length; i++ ){
      var completed_date = (criteria[i].completed_date)? convertDate(criteria[i].completed_date) : null;

      if ( 'Class' == criteria[i].type ) {
        classes.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: completed_date });
      } else if( 'Exam' == criteria[i].type ){
        exams.push({ID: i, name: criteria[i].name, completed: criteria[i].completed, completed_date: completed_date });
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
      badge.slug = data.data.slug;
      $(badge.element).append( badgeTemplate( badge ) );
      sortThings('.badge-container', badge.element);
    });
  }

  /**
   * Displays a certificate, HTML criteria, and "Add to Backpack" button
   *
   * @param      {object}  certificate  The certificate
   */
  var displayCertificate = function( certificate ){

    $.getJSON( wpvars.criteriaurl, {name: certificate.name}, function(data){
      certificate.image = ( typeof data.data.image !== 'undefined' )? data.data.image : wpvars.default_badge;

      var criteria = certificate.criteria;
      for( var i = 0; i < criteria.length; i++ ){
        var completed_date = (criteria[i].completed_date)? convertDate(criteria[i].completed_date) : null;
        switch( criteria[i].type ){
          case 'Class':
            criteria[i].name = 'Attend ' + criteria[i].name;
            break;
          case 'Exam':
            criteria[i].name = 'Pass the ' + criteria[i].name;
            break;
          default:
            // nothing
        }
        criteria[i].completed_date = completed_date;
      }

      certificate.criteria = criteria;

      if( 0 < certificate.badges.length ){
        for (var i = certificate.badges.length - 1; i >= 0; i--) {
          var completed = certificate.badges[i].completed;
          var name = (completed)? certificate.badges[i].name + ' Badge' : 'Earn the ' + certificate.badges[i].name + ' Badge';
          certificate.criteria.unshift({
            'name': name,
            'type': false,
            'completed': completed,
            'completed_date': certificate.badges[i].completed_date
          })
        }
      }

      // Add "Complete X Badges" note to front of criteria array
      if( 0 < certificate.additional_badges_required ){
        certificate.criteria.unshift({
          'name': 'Complete ' + certificate.additional_badges_required + ' badges',
          'type': false,
          'completed': (certificate.additional_badges_completed >= certificate.additional_badges_required ),
          'completed_date': false
        });
      }


      switch(data.data.slug){
        case 'product-owner-practitioner':
          // Add `ah` to the slug to get it to sort
          // immediately after `agile-analysis-practitioner`
          certificate.slug = 'ah-' + data.data.slug;
          break;

        default:
          certificate.slug = data.data.slug;
      }

      if( certificate.completed_date )
        certificate.completed_date = convertDate(certificate.completed_date);

      $(certificate.element).append( certificateTemplate( certificate ) );
      sortThings('.certificate-container', certificate.element);
    })
  }

  /**
   * Sorts `things` alphabetically
   */
  var sortThings = function(child, parent){
    $(child, parent).sort(function(a,b){
      return $(a).attr('id') > $(b).attr('id');
    }).appendTo(parent);
  }

  var convertDate = function(date){
    year = date.substr(0,4);
    month = parseInt(date.substr(5,2))-1;
    day = date.substr(8,2);

    return new Date(year,month,day);
  }
})(jQuery);