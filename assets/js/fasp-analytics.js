// fasp-analytics.js - Track user activity on dashboard
(function($) {
  'use strict';
  
  if (typeof faspAnalytics === 'undefined') {
    return;
  }
  
  /**
   * Track activity via AJAX
   */
  function trackActivity(activity, meta) {
    meta = meta || {};
    
    $.ajax({
      url: faspAnalytics.ajaxUrl,
      type: 'POST',
      data: {
        action: 'fasp_track_activity',
        nonce: faspAnalytics.nonce,
        activity: activity,
        meta: meta
      }
    });
  }
  
  // Track page views
  $(document).ready(function() {
    var currentPage = window.location.pathname;
    
    if (currentPage.indexOf('forex-dashboard') !== -1) {
      trackActivity('view_dashboard');
    } else if (currentPage.indexOf('platforms') !== -1) {
      trackActivity('view_platforms');
    } else if (currentPage.indexOf('resources') !== -1) {
      trackActivity('view_resources');
    } else if (currentPage.indexOf('coaches') !== -1) {
      trackActivity('view_coaches');
    }
  });
  
  // Track card clicks
  $(document).on('click', '.fasp-card', function() {
    var cardTitle = $(this).find('h2, h3').first().text();
    trackActivity('click_card', {
      card: cardTitle
    });
  });
  
  // Track button clicks
  $(document).on('click', '.button, .fasp-qa', function() {
    var buttonText = $(this).text().trim();
    trackActivity('click_button', {
      button: buttonText
    });
  });
  
  // Track platform card interactions
  $(document).on('click', '.fasp-platform', function() {
    var platform = $(this).data('platform') || $(this).find('h3').text();
    trackActivity('click_platform', {
      platform: platform
    });
  });
  
  // Track resource interactions
  $(document).on('click', 'a[href*="fasp_resource"]', function() {
    var resourceTitle = $(this).closest('.fasp-card').find('h3').text();
    trackActivity('click_resource', {
      resource: resourceTitle
    });
  });
  
  // Track coach interactions
  $(document).on('click', 'a[href*="fasp_coach"]', function() {
    var coachName = $(this).closest('.fasp-card').find('h3').text();
    trackActivity('click_coach', {
      coach: coachName
    });
  });
  
  // Track demo to live CTA clicks
  $(document).on('click', '.fasp-demo-to-live-cta .button', function() {
    trackActivity('click_demo_to_live_cta');
  });
  
  // Track checklist interactions
  $(document).on('click', '.fasp-checklist li', function() {
    var item = $(this).text().trim();
    trackActivity('click_checklist_item', {
      item: item
    });
  });
  
  // Track education panel interactions
  $(document).on('click', '.fasp-education-item a', function() {
    var section = $(this).closest('.fasp-education-item').find('h4').text();
    trackActivity('click_education', {
      section: section
    });
  });
  
  // Track scroll depth
  var scrollDepthTracked = {25: false, 50: false, 75: false, 100: false};
  
  $(window).on('scroll', function() {
    var scrollPercentage = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
    
    $.each(scrollDepthTracked, function(depth, tracked) {
      if (scrollPercentage >= depth && !tracked) {
        scrollDepthTracked[depth] = true;
        trackActivity('scroll_depth', {
          depth: depth + '%'
        });
      }
    });
  });
  
  // Track time on page
  var startTime = new Date().getTime();
  
  $(window).on('beforeunload', function() {
    var endTime = new Date().getTime();
    var timeSpent = Math.floor((endTime - startTime) / 1000); // seconds
    
    trackActivity('time_on_page', {
      seconds: timeSpent
    });
  });
  
})(jQuery);
