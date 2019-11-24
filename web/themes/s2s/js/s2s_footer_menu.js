jQuery(document).ready(function(){
    jQuery('.csv-feed .feed-icon').text('Export Report');
    
    //jQuery('select[name="webform_submission_value_3[select]"]').prepend('<option value="" selected>- Any -</option>');
    //jQuery('select[name="webform_submission_value[select]"]').prepend('<option value="" selected>- Any -</option>');
    
    jQuery('ul.pager.js-pager__items li a').each(function () {
        var link = jQuery(this).attr('href');
        if(link.indexOf('webform_submission_value_3%5Bselect%5D') == -1){
            link += '&webform_submission_value_3%5Bselect%5D='+jQuery('select[name="webform_submission_value_3[select]"]').val();
        }
        if(link.indexOf('webform_submission_value%5Bselect%5D') == -1){
            link += '&webform_submission_value%5Bselect%5D='+jQuery('select[name="webform_submission_value[select]"]').val();
        }
        if(link.indexOf('created%5Bmin%5D') == -1){
            link += '&created%5Bmin%5D='+jQuery('#edit-created-min').val();
        }
        if(link.indexOf('created%5Bmax%5D') == -1){
            link += '&created%5Bmax%5D='+jQuery('#edit-created-max').val();
        }
        jQuery(this).attr('href', link);
        //console.log(link);
    });
    
    jQuery('.path-screening-reports .view-screening-reports #edit-created-min, .path-screening-reports .view-screening-reports #edit-created-max').prop('type', 'text');  
    jQuery('.path-screening-reports .view-screening-reports #edit-created-min, .path-screening-reports .view-screening-reports #edit-created-max').prop('placeholder', 'Click for a calendar');  
    
    Drupal.behaviors.customDatepicker = {
    attach: function (context, settings) {
      jQuery(function () {        
        jQuery(".path-screening-reports .view-screening-reports #edit-created-min,.path-screening-reports .view-screening-reports #edit-created-max").datepicker({
            dateFormat: "yy-mm-dd"
        });
        /*jQuery(".path-screening-reports .view-screening-reports #edit-created-max").datepicker({
            dateFormat: "mm/dd/yy",
            altField: "input[data-drupal-selector=edit-created-max]",
            altFormat: "yy-mm-dd"
        });*/
      });
    }
  };
});