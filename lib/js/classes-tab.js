/* Classes Tab */
(function($){

  HandlebarsIntl.registerWith(Handlebars);
  var classesTemplate = Handlebars.compile( $('#classes-template').html() );

  /**
   * Get the user's classes data from Sales Force
   */
  if( '' != wpvars.student_id ){
    $.ajax({
      url: wpvars.jsonurl + 'getStudentClasses/?student_id=' + wpvars.student_id,
      type: 'GET',
      dataType: 'json',
      success: function(response){
        var classes = response.data.records;
        $('#classes .alert').fadeOut(300,function(){
          $('#classes').append( classesTemplate( {classes: classes} ) );
        });
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
    $('#classes .alert').fadeOut();
    $('#classes').fadeIn().html('<div class="alert alert-error"><strong>No Class Data Found</strong><br/>We were unable to retrieve any Class Data for your account email address (<em>' + wpvars.student_email + '</em>). Please contact B2T Training and alert them to this error. Be sure to mention this email address: <em>' + wpvars.student_email + '</em>.</div>');
  }
})(jQuery);