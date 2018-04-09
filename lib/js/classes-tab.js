/* Classes Tab */
(function($){

  HandlebarsIntl.registerWith(Handlebars);
  var classesTemplate = Handlebars.compile( $('#classes-template').html() );
  var examRowTemplate = Handlebars.compile( $('#exam-row-template').html() );

  /**
   * Get the user's classes data from Sales Force
   */
  if( '' != wpvars.student_id ){
    var classDataURL = wpvars.jsonurl + 'getStudentClasses/?student_id=' + wpvars.student_id;
    //console.log(classDataURL);
    $.ajax({
      url: classDataURL,
      type: 'GET',
      dataType: 'json',
      success: function(response){
        var rawClasses = response.data.records;
        console.log(rawClasses);
        var classes = [];
        for (var i = 0; i < rawClasses.length; i++) {
          var singleClass = rawClasses[i];
          var date = (singleClass.Class__r.End_Date__c)? convertDate(singleClass.Class__r.End_Date__c) : null;
          classes[i] = {
            'name': singleClass.Class__r.Name,
            'date': date
          };
        }
        $('#classes .alert').fadeOut(300,function(){
          $('#classes').append( classesTemplate( {classes: classes} ) );
          sortTable($('table.classes'),'asc');
        });

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
      console.log(examDataURL);
      $.ajax({
        url: examDataURL,
        type: 'GET',
        dataType: 'json',
        success: function(response){
          var rawExams = response.data.records;
          console.log(rawExams);
          var exams = [];
          for(var i = 0; i < rawExams.length; i++){
            var singleExam = rawExams[i];
            var date = (singleExam.Passed_Date__c)? convertDate(singleExam.Passed_Date__c) : null;
            exams[i] = {
              'name': singleExam.Course__r.Name,
              'date': date
            };
          }
          $('#classes table.classes tbody').append( examRowTemplate( {exams: exams} ) );
          sortTable($('table.classes'),'asc');
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

  /**
   * Sorts a table alphabetically
   */
  function sortTable(table, order) {
      var asc   = order === 'asc',
          tbody = table.find('tbody');

      tbody.find('tr').sort(function(a, b) {
          if (asc) {
              return $('td:first', a).text().localeCompare($('td:first', b).text());
          } else {
              return $('td:first', b).text().localeCompare($('td:first', a).text());
          }
      }).appendTo(tbody);
  }

  var convertDate = function(date){
    year = date.substr(0,4);
    month = parseInt(date.substr(5,2))-1;
    day = date.substr(8,2);

    return new Date(year,month,day);
  }
})(jQuery);