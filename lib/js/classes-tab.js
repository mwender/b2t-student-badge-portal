/* Classes Tab */
(function($){

  HandlebarsIntl.registerWith(Handlebars);
  var tableRowTemplate = Handlebars.compile( $('#table-row-template').html() );

  /**
   * Get the user's classes data from Sales Force
   */
  if( '' != wpvars.student_id ){
    var classDataURL = wpvars.jsonurl + 'getStudentClasses/?student_id=' + wpvars.student_id;
    $.ajax({
      url: classDataURL,
      type: 'GET',
      dataType: 'json',
      success: function(response){
        if( 0 === response.totalSize ){
          // nothing
        } else {
          var rawClasses = response.records;
          var classes = [];
          for (var i = 0; i < rawClasses.length; i++) {
            var singleClass = rawClasses[i];
            var date = (singleClass.Class__r.End_Date__c)? convertDate(singleClass.Class__r.End_Date__c) : null;

            // Get the timestamp
            if( null != singleClass.Class__r.End_Date__c ){
              var dateArray = singleClass.Class__r.End_Date__c.split('-');
              var ts = new Date(dateArray[0],dateArray[1],dateArray[2]).getTime();
              classes[i] = {
                'name': singleClass.Class__r.Name,
                'date': date,
                'timestamp': ts
              };
            }
          }
          $('table.classes tbody').html( tableRowTemplate( {rows: classes} ) );

        }
        getStudentExams();
      },
      error: function(response){
        console.log('There was an error retriving the Student Class Data.');
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

  /**
   * Gets the student exams.
   */
  function getStudentExams(){
    if( '' != wpvars.student_id ){
      var examDataURL = wpvars.jsonurl + 'getStudentExams/?student_id=' + wpvars.student_id;
      $.ajax({
        url: examDataURL,
        type: 'GET',
        dataType: 'json',
        success: function(response){
          console.log('Exam response =',response);
          $('table.classes .alert-row').fadeOut();
          if( 0 === response.totalSize ){
            // nothing
          } else {
            var rawExams = response.records;
            var exams = [];
            for(var i = 0; i < rawExams.length; i++){
              var singleExam = rawExams[i];
              var date = (singleExam.Passed_Date__c)? convertDate(singleExam.Passed_Date__c) : null;
              console.log('date = ',date);

              // Get the timestamp
              if( null != singleExam.Passed_Date__c ){
                var dateArray = singleExam.Passed_Date__c.split('-');
                var ts = new Date(dateArray[0],dateArray[1],dateArray[2]).getTime();
                exams[i] = {
                  'name': singleExam.Course__r.Name,
                  'date': date,
                  'timestamp': ts
                };
              }
            }
            $('#classes table.classes tbody').append( tableRowTemplate( {rows: exams} ) );
            window.setTimeout(function(){
              $('#classes table.classes').tablesorter({
                sortList: [[1,1]]
              });
            }, 200);
          }

        },
        error: function(response){
        console.log('There was an error retriving the Student Exam Data.');
        console.log(response.responseJSON.message);
        },
        beforeSend: function(xhr){
          xhr.setRequestHeader('X-WP-Nonce',wpvars.nonce);
        }
      });
    }
  }

  var convertDate = function(date){
    year = date.substr(0,4);
    month = parseInt(date.substr(5,2))-1;
    day = date.substr(8,2);

    return new Date(year,month,day);
  }
})(jQuery);