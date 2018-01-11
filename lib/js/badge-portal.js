/* badge-portal.js */
(function($){
  console.log('badge-portal.js is loaded.');
  console.log('wpvars.jsonurl = ' + wpvars.jsonurl);
  console.log('wpvars.user_email = ' + wpvars.user_email);
  console.log('full URL: ' + wpvars.jsonurl + wpvars.user_email);

  $.getJSON( wpvars.jsonurl + wpvars.user_email, function(data){
    console.log('JSON data returned:');
    console.log(data);
    var badges = data.badges;
    var badgeTemplate = Handlebars.compile( $('#badge-template').html() );
    for (var i = 0; i < badges.length; i++) {
      $('#badge-display').append( badgeTemplate( badges[i] ) );
      console.log(badges[i]);
    }
  });
})(jQuery);