/* Classes Tab with Sorting (Updated, Custom sort only) */
(function($){

  HandlebarsIntl.registerWith(Handlebars);
  var tableRowTemplate = Handlebars.compile($('#table-row-template').html());

  let rawClasses = [];
  let currentSort = { field: 'name', direction: 'asc' };

  function renderTable(data) {
    $('table.classes tbody').html(tableRowTemplate({ rows: data, student_email: wpvars.student_email }));
    updateSortIndicators();
  }

  function sortClasses(classes, field, direction) {
    return [...classes].sort(function(a, b) {
      let valA = a[field];
      let valB = b[field];

      if (field === 'name') {
        valA = valA.toUpperCase();
        valB = valB.toUpperCase();
      }

      if (valA < valB) return direction === 'asc' ? -1 : 1;
      if (valA > valB) return direction === 'asc' ? 1 : -1;
      return 0;
    });
  }

  function applySortAndRender() {
    const sorted = sortClasses(rawClasses, currentSort.field, currentSort.direction);
    renderTable(sorted);
  }

  function updateSortIndicators() {
    $('th.sortable').each(function() {
      const $th = $(this);
      const field = $th.data('sort');
      $th.removeClass('sorted-asc sorted-desc');
      $th.find('.sort-indicator').remove();
      if (field === currentSort.field) {
        const dirClass = currentSort.direction === 'asc' ? 'sorted-asc' : 'sorted-desc';
        const arrow = currentSort.direction === 'asc' ? '▲' : '▼';
        $th.addClass(dirClass).append('<span class="sort-indicator" style="margin-left: 6px;">' + arrow + '</span>');
      }
    });
  }

  $(document).on('click', 'th.sortable', function() {
    const field = $(this).data('sort');
    if (currentSort.field === field) {
      currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      currentSort.field = field;
      currentSort.direction = 'asc';
    }
    applySortAndRender();
  });

  /**
   * Get the user's classes data from Student CRM
   */
  if ('' != wpvars.student_id) {
    var classDataURL = wpvars.jsonurl + 'getStudentClasses/?student_id=' + wpvars.student_id;
    console.log('classDataURL = ', classDataURL);
    $.ajax({
      url: classDataURL,
      type: 'GET',
      dataType: 'json',
      success: function(response) {
        if (0 === response.totalSize) {
          // nothing
        } else {
          let records = response?.records;

          if (!Array.isArray(records)) {
            console.error("❌ Expected response.records to be an array, got:", records);
            records = [];
          }
          console.log('🔔 Class records =', records);
          rawClasses = [];
          for (let i = 0; i < records.length; i++) {
            let r = records[i];
            if (r.Class__r.End_Date__c) {
              let date = convertDate(r.Class__r.End_Date__c);
              let ts = date.getTime();
              rawClasses.push({
                name: r.Class__r.Name,
                date: date,
                timestamp: ts,
                resource_id: r.resource_page.id,
                link_text: r.resource_page.link_text
              });
            }
          }
          applySortAndRender();
        }
        getStudentExams();
      },
      error: function(response) {
        console.warn('There was an error retriving the Student Class Data.');
        console.warn('🔔 response = ', response);
      },
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', wpvars.nonce);
      }
    });
  } else {
    $('#classes .alert').fadeOut();
    $('#classes').fadeIn().html('<div class="alert alert-error"><strong>No Class Data Found</strong><br/>We were unable to retrieve any Class Data for your account email address (<em>' + wpvars.student_email + '</em>). Please contact B2T Training and alert them to this error. Be sure to mention this email address: <em>' + wpvars.student_email + '</em>.</div>');
  }

  /**
   * Gets the student exams.
   */
  function getStudentExams() {
    if ('' != wpvars.student_id) {
      var examDataURL = wpvars.jsonurl + 'getStudentExams/?student_id=' + wpvars.student_id;
      $.ajax({
        url: examDataURL,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          console.log('Exam response =', response);
          $('table.classes .alert-row').fadeOut();

          if (0 !== response.totalSize) {
            var rawExams = response.records;
            for (var i = 0; i < rawExams.length; i++) {
              var singleExam = rawExams[i];
              if (singleExam.Passed_Date__c) {
                var date = convertDate(singleExam.Passed_Date__c);
                var ts = date.getTime();
                rawClasses.push({
                  name: singleExam.Course__r.Name,
                  date: date,
                  timestamp: ts
                });
              }
            }
            applySortAndRender();
          }
        },
        error: function(response) {
          console.log('There was an error retriving the Student Exam Data.');
          console.log(response.responseJSON.message);
        },
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-WP-Nonce', wpvars.nonce);
        }
      });
    }
  }

  function convertDate(date) {
    const year = date.substr(0, 4);
    const month = parseInt(date.substr(5, 2)) - 1;
    const day = date.substr(8, 2);
    return new Date(year, month, day);
  }

})(jQuery);
