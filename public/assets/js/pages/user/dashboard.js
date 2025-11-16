"use strict";

// Dashboard configuration
const Dashboard = {
  // DOM elements
  elements: {
    totalDevice: '#total-device',
    totalMessages: '#total-messages', 
    totalContacts: '#total-contacts',
    globalPeriod: '#global-period',
    customDateRange: '#custom-date-range',
    startDate: '#start-date',
    endDate: '#end-date',
    applyCustomRange: '#apply-custom-range',
    messagesChart: '#chart-sales',
    deviceChart: '#chart-device-performance',
    staticDataUrl: '#static-data'
  },

  // Chart data
  chartData: {
    messages: {
      days: [],
      total: [],
      sent: [],
      received: []
    },
    devices: []
  },

  // Current period settings
  currentPeriod: {
    type: 'predefined', // 'predefined' or 'custom'
    days: 7,
    startDate: null,
    endDate: null
  },

  // Initialize dashboard
  init() {
    this.checkSuccessAlert();
    this.loadInitialData();
    this.bindEvents();
    this.initializeDateInputs();
  },

  // Initialize date inputs with current values
  initializeDateInputs() {
    const now = new Date();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(now.getDate() - 6);
    
    // Set default values
    $(this.elements.startDate).val(this.formatDateTimeLocal(sevenDaysAgo));
    $(this.elements.endDate).val(this.formatDateTimeLocal(now));
    
    // Set max to today (disable future dates)
    const todayMax = this.formatDateTimeLocal(now);
    $(this.elements.startDate).attr('max', todayMax);
    $(this.elements.endDate).attr('max', todayMax);
    
    // Set initial min/max constraints
    this.updateDateConstraints();
    
    // Trigger initial validation to set up tooltips
    this.validateDateRange();
  },

  // Validate date range and update tooltip
  validateDateRange() {
    const startDate = $(this.elements.startDate).val();
    const endDate = $(this.elements.endDate).val();
    
    let isValid = true;
    let errorMessage = '';
    
    if (startDate && endDate) {
      const startDateObj = new Date(startDate);
      const endDateObj = new Date(endDate);
      const daysDiff = Math.ceil((endDateObj - startDateObj) / (1000 * 60 * 60 * 24));
      
      if (startDateObj > endDateObj) {
        isValid = false;
        errorMessage = 'Start date must be before end date';
      } else if (daysDiff > 90) {
        isValid = false;
        errorMessage = 'Maximum date range is 90 days';
      }
    }
    
    const $applyButton = $(this.elements.applyCustomRange);
    $applyButton.prop('disabled', !isValid);
    
    // Add/remove tooltip based on validation
    if (!isValid && errorMessage) {
      $applyButton.attr('title', errorMessage);
      $applyButton.attr('data-toggle', 'tooltip');
      $applyButton.attr('data-placement', 'top');
      
      // Initialize Bootstrap tooltip if available
      if (typeof $applyButton.tooltip === 'function') {
        $applyButton.tooltip('dispose').tooltip();
      }
    } else {
      // Remove tooltip when valid
      $applyButton.removeAttr('title');
      $applyButton.removeAttr('data-toggle');
      $applyButton.removeAttr('data-placement');
      
      // Dispose Bootstrap tooltip if it exists
      if (typeof $applyButton.tooltip === 'function') {
        $applyButton.tooltip('dispose');
      }
    }
  },

  // Update date constraints based on current selections
  updateDateConstraints() {
    const startDate = $(this.elements.startDate).val();
    if (startDate) {
      const startDateObj = new Date(startDate);
      const maxEndDate = new Date(startDateObj);
      maxEndDate.setDate(maxEndDate.getDate() + 90); // 90 days from start
      
      const today = new Date();
      // End date can't be more than 90 days from start OR future than today
      const finalMaxEnd = maxEndDate > today ? today : maxEndDate;
      
      $(this.elements.endDate).attr('min', this.formatDateTimeLocal(startDateObj));
      $(this.elements.endDate).attr('max', this.formatDateTimeLocal(finalMaxEnd));
    }
    
    // Revalidate after updating constraints
    this.validateDateRange();
  },

  // Format date for datetime-local input
  formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
  },

  // Update date inputs based on predefined selection
  updateDateInputsFromPredefined(days) {
    const now = new Date();
    const startDate = new Date();
    startDate.setDate(now.getDate() - (days - 1));
    
    $(this.elements.startDate).val(this.formatDateTimeLocal(startDate));
    $(this.elements.endDate).val(this.formatDateTimeLocal(now));
    
    // Update constraints after setting values
    this.updateDateConstraints();
  },

  // Check for success alerts and trigger animations
  checkSuccessAlert() {
    const successAlert = $('.success-alert');
    if (successAlert.length > 0) {
      this.congratulations();
      this.congratulationsPride();
    }
  },

  // Load initial dashboard data
  loadInitialData() {
    const url = $(this.elements.staticDataUrl).val();
    
    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      success: (response) => this.handleDataSuccess(response),
      error: (xhr, status, error) => this.handleError(error)
    });
  },

  // Handle successful data response
  handleDataSuccess(response) {
    // Update stat cards
    $(this.elements.totalDevice).html(response.devicesCount);
    $(this.elements.totalMessages).html(response.messagesCount);
    $(this.elements.totalContacts).html(response.contactCount);

    // Process chart data
    this.processMessagesData(response.messagesStatics);
    this.processDeviceData(response.devicePerformance);
    
    // Render charts
    this.renderMessagesChart();
    this.renderDeviceChart();
  },

  // Process messages chart data
  processMessagesData(messagesStatics) {
    this.chartData.messages = {
      days: [],
      total: [],
      sent: [],
      received: []
    };

    messagesStatics.forEach(item => {
      this.chartData.messages.days.push(item.date);
      this.chartData.messages.total.push(item.total_count);
      this.chartData.messages.sent.push(item.sent_count);
      this.chartData.messages.received.push(item.received_count);
    });
  },

  // Process device performance data
  processDeviceData(devicePerformance) {
    this.chartData.devices = devicePerformance;
  },



  // Load data for predefined period
  loadPredefinedPeriod(days) {
    this.currentPeriod = {
      type: 'predefined',
      days: days,
      startDate: null,
      endDate: null
    };

    const url = $(this.elements.staticDataUrl).val();
    
    // Use single endpoint with days parameter
    $.ajax({
      url: `${url}?days=${days}`,
      method: 'GET',
      dataType: 'json',
      success: (response) => {
        // Process chart data only (no stat cards on period change)
        this.processMessagesData(response.messagesStatics);
        this.processDeviceData(response.devicePerformance);
        this.renderMessagesChart();
        this.renderDeviceChart();
      },
      error: (error) => this.handleError(error)
    });
  },

  // Load data for custom date range
  loadCustomDateRange(startDate, endDate) {
    this.currentPeriod = {
      type: 'custom',
      days: null,
      startDate: startDate,
      endDate: endDate
    };

    const url = $(this.elements.staticDataUrl).val();
    const params = `?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
    
    // Use single endpoint with custom date parameters
    $.ajax({
      url: `${url}${params}`,
      method: 'GET',
      dataType: 'json',
      success: (response) => {
        // Process chart data only (no stat cards on period change)
        this.processMessagesData(response.messagesStatics);
        this.processDeviceData(response.devicePerformance);
        this.renderMessagesChart();
        this.renderDeviceChart();
      },
      error: (error) => this.handleError(error)
    });
  },

  // Bind event handlers
  bindEvents() {
    // Predefined period selection
    $(this.elements.globalPeriod).on('change', (e) => {
      const value = $(e.target).val();
      
      if (value === 'custom') {
        // Show custom date range inputs
        $(this.elements.customDateRange).show();
      } else {
        // Hide custom date range inputs and load predefined period
        $(this.elements.customDateRange).hide();
        const days = parseInt(value);
        this.updateDateInputsFromPredefined(days);
        this.loadPredefinedPeriod(days);
      }
    });

    // Apply custom date range
    $(this.elements.applyCustomRange).on('click', () => {
      const startDate = $(this.elements.startDate).val();
      const endDate = $(this.elements.endDate).val();
      
      if (!startDate || !endDate) {
        alert('Please select both start and end dates.');
        return;
      }
      
      if (new Date(startDate) > new Date(endDate)) {
        alert('Start date must be before end date.');
        return;
      }
      
      this.loadCustomDateRange(startDate, endDate);
    });

    // Real-time validation for date inputs
    $(this.elements.startDate + ', ' + this.elements.endDate).on('change', () => {
      const startDate = $(this.elements.startDate).val();
      
      // Update constraints when start date changes
      if (startDate) {
        this.updateDateConstraints();
      } else {
        // Validate even if no start date to handle tooltip properly
        this.validateDateRange();
      }
    });
  },

  // Render messages transaction chart
  renderMessagesChart() {
    const $chart = $(this.elements.messagesChart);
    
    // Destroy existing chart if it exists
    const existingChart = $chart.data('chart');
    if (existingChart) {
      existingChart.destroy();
    }

    // Create new chart
    const chart = new Chart($chart, {
      type: 'line',
      data: {
        labels: this.chartData.messages.days,
        datasets: [
          {
            label: 'Sent',
            data: this.chartData.messages.sent,
            borderColor: Charts.colors.theme['success'],
            backgroundColor: Charts.colors.theme['success'],
            borderWidth: 2,
            pointBackgroundColor: Charts.colors.theme['success'],
            fill: false
          },
          {
            label: 'Received', 
            data: this.chartData.messages.received,
            borderColor: Charts.colors.theme['info'],
            backgroundColor: Charts.colors.theme['info'],
            borderWidth: 2,
            pointBackgroundColor: Charts.colors.theme['info'],
            fill: false
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          yAxes: [{
            gridLines: {
              color: Charts.colors.gray[200],
              zeroLineColor: Charts.colors.gray[200]
            },
            ticks: {
              beginAtZero: true
            }
          }],
          xAxes: [{
            gridLines: {
              color: Charts.colors.gray[200]
            }
          }]
        },
        elements: {
          point: {
            radius: 4,
            hoverRadius: 6
          }
        },
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            beforeBody: (tooltipItems) => {
              const index = tooltipItems[0].index;
              const total = this.chartData.messages.total[index];
              return `Total: ${total}`;
            }
          }
        },
        legend: {
          display: true,
          position: 'top'
        }
      }
    });

    // Store chart reference
    $chart.data('chart', chart);
  },

  // Render device performance chart
  renderDeviceChart() {
    const $chart = $(this.elements.deviceChart);
    
    // Destroy existing chart if it exists
    const existingChart = $chart.data('chart');
    if (existingChart) {
      existingChart.destroy();
    }

    // Prepare data
    const deviceNames = this.chartData.devices.map(device => device.device_name);
    const sentData = this.chartData.devices.map(device => device.sent_count);
    const receivedData = this.chartData.devices.map(device => device.received_count);

    // Create new chart
    const chart = new Chart($chart, {
      type: 'horizontalBar',
      data: {
        labels: deviceNames,
        datasets: [
          {
            label: 'Sent',
            data: sentData,
            backgroundColor: Charts.colors.theme['success'],
            borderColor: Charts.colors.theme['success'],
            borderWidth: 1
          },
          {
            label: 'Received',
            data: receivedData,
            backgroundColor: Charts.colors.theme['info'],
            borderColor: Charts.colors.theme['info'],
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          xAxes: [{
            gridLines: {
              color: Charts.colors.gray[200],
              zeroLineColor: Charts.colors.gray[200]
            },
            ticks: {
              beginAtZero: true
            }
          }],
          yAxes: [{
            gridLines: {
              color: Charts.colors.gray[200]
            }
          }]
        },
        tooltips: {
          mode: 'index',
          intersect: false,
          callbacks: {
            beforeBody: (tooltipItems) => {
              const index = tooltipItems[0].index;
              const device = this.chartData.devices[index];
              const total = device.total_count;
              const status = device.status === 1 ? 'Online' : 'Offline';
              return [`Total: ${total}`, `Status: ${status}`];
            }
          }
        },
        legend: {
          display: true,
          position: 'top'
        }
      }
    });

    // Store chart reference
    $chart.data('chart', chart);
  },

  

  // Handle AJAX errors
  handleError(error) {
    console.error('Dashboard Error:', error);
    // You could show a user-friendly error message here
  },

  // Success animations (keeping existing functions)
  congratulations() {
    if (typeof congratulations === 'function') {
      congratulations();
    }
  },

  congratulationsPride() {
    if (typeof congratulationsPride === 'function') {
      congratulationsPride();
    }
  }
};

// Initialize dashboard when DOM is ready
$(document).ready(() => {
  Dashboard.init();
});