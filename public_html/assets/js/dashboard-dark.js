(function($) {
  'use strict';
  $(function() {
      if ($("#performanceLine").length) {
          const ctx = document.getElementById('performanceLine');
          var graphGradient = document.getElementById("performanceLine").getContext('2d');
          var graphGradient2 = document.getElementById("performanceLine").getContext('2d');
          var saleGradientBg = graphGradient.createLinearGradient(5, 0, 5, 100);
          saleGradientBg.addColorStop(0, 'rgba(251, 150, 0, 0.18)');
          saleGradientBg.addColorStop(1, 'rgba(251, 150, 0, 0.02)');
          var saleGradientBg2 = graphGradient2.createLinearGradient(100, 0, 50, 150);
          saleGradientBg2.addColorStop(0, 'rgba(251, 255, 255, 0)');
          saleGradientBg2.addColorStop(1, 'rgba(251, 255, 255, 0)');

          new Chart(ctx, {
              type: 'line',
              data: {
                  labels: ["SUN", "sun", "MON", "mon", "TUE", "tue", "WED", "wed", "THU", "thu", "FRI", "fri", "SAT"],
                  datasets: [{
                      label: 'This week',
                      data: [50, 110, 60, 290, 200, 115, 130, 170, 90, 210, 240, 280, 200],
                      backgroundColor: saleGradientBg,
                      borderColor: [
                          '#F29F67',
                      ],
                      borderWidth: 1.5,
                      fill: true, // 3: no fill
                      pointBorderWidth: 1,
                      pointRadius: [4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
                      pointHoverRadius: [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
                      pointBackgroundColor: ['#F29F67', '#F29F67', '#F29F67', '#F29F67)', '#F29F67', '#F29F67', '#F29F67', '#F29F67)', '#F29F67', '#F29F67', '#F29F67', '#F29F67)'],
                      pointBorderColor: ['#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', ],
                  }, {
                      label: 'Last week',
                      data: [30, 150, 190, 250, 120, 150, 130, 20, 30, 15, 40, 95, 180],
                      backgroundColor: saleGradientBg2,
                      borderColor: [
                          '#808191',
                      ],
                      borderWidth: 1.5,
                      fill: true, // 3: no fill
                      pointBorderWidth: 1,
                      pointRadius: [0, 0, 0, 4, 0],
                      pointHoverRadius: [0, 0, 0, 2, 0],
                      pointBackgroundColor: ['#808191)', '#808191', '#808191', '#808191', '#808191)', '#808191', '#808191', '#808191', '#808191)', '#808191', '#808191', '#808191', '#808191)'],
                      pointBorderColor: ['#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', '#252730', ],
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  elements: {
                      line: {
                          tension: 0.4,
                      }
                  },
                  scales: {
                      y: {
                        border: {
                          display: false
                        },
                          grid: {
                              display: true,
                              drawTicks: false,
                              color: "#383A42",
                              zeroLineColor: '#383A42',
                          },
                          ticks: {
                              beginAtZero: false,
                              autoSkip: true,
                              maxTicksLimit: 4,
                              color: "#808191",
                              font: {
                                  size: 10,
                              }
                          }
                      },
                      x: {
                        border: {
                          display: false
                        },
                          grid: {
                              display: false,
                              drawTicks: false,
                          },
                          ticks: {
                              beginAtZero: false,
                              autoSkip: true,
                              maxTicksLimit: 7,
                              color: "#6B778C",
                              font: {
                                  size: 10,
                              }
                          }
                      }
                  },
                  plugins: {
                      legend: {
                          display: false,
                      },
                      tooltips: {
                          backgroundColor: 'rgba(31, 59, 179, 1)',
                      },
                  }
              },
              plugins: [{
                  afterDatasetUpdate: function(chart, args, options) {
                      const chartId = chart.canvas.id;
                      var i;
                      const legendId = `${chartId}-legend`;
                      const ul = document.createElement('ul');
                      for (i = 0; i < chart.data.datasets.length; i++) {
                          ul.innerHTML += `
                <li>
                  <span style="background-color: ${chart.data.datasets[i].borderColor}"></span>
                  ${chart.data.datasets[i].label}
                </li>
              `;
                      }
                      return document.getElementById(legendId).appendChild(ul);
                  }
              }]
          });
      }

      if ($("#status-summary").length) {
          const statusSummaryChartCanvas = document.getElementById('status-summary');
          new Chart(statusSummaryChartCanvas, {
              type: 'line',
              data: {
                  labels: ["SUN", "MON", "TUE", "WED", "THU", "FRI"],
                  datasets: [{
                      label: '# of Votes',
                      data: [50, 68, 70, 10, 12, 80],
                      backgroundColor: "#ffcc00",
                      borderColor: [
                          '#01B6A0',
                      ],
                      borderWidth: 2,
                      fill: false, // 3: no fill
                      pointBorderWidth: 0,
                      pointRadius: [0, 0, 0, 0, 0, 0],
                      pointHoverRadius: [0, 0, 0, 0, 0, 0],
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  elements: {
                      line: {
                          tension: 0.4,
                      }
                  },
                  scales: {
                      y: {
                        border: {
                          display: false
                        },
                          display: false,
                          grid: {
                              display: false,
                          },
                      },
                      x: {
                        border: {
                          display: false
                        },
                          display: false,
                          grid: {
                              display: false,
                          }
                      }
                  },
              },
              plugins: {
                  legend: {
                      display: false,
                  },
                  tooltips: {
                      backgroundColor: 'rgba(31, 59, 179, 1)',
                  },
              }
          });
      }

      if ($("#marketingOverview").length) {
          const marketingOverviewCanvas = document.getElementById('marketingOverview');
          new Chart(marketingOverviewCanvas, {
              type: 'bar',
              data: {
                  labels: ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"],
                  datasets: [{
                      label: 'Last week',
                      data: [110, 220, 200, 190, 220, 110, 210, 110, 205, 202, 201, 150],
                      backgroundColor: "#F29F67",
                      borderColor: [
                          '#F29F67',
                      ],
                      borderWidth: 0,
                      fill: true, // 3: no fill
                      barPercentage: .4,

                  }, {
                      label: 'This week',
                      data: [215, 290, 210, 250, 290, 230, 290, 210, 280, 220, 190, 300],
                      backgroundColor: "#5A5B6A",
                      borderColor: [
                          '#5A5B6A',
                      ],
                      borderWidth: 0,
                      fill: true, // 3: no fill
                      barPercentage: .4,
                  }]
              },
              options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  elements: {
                      line: {
                          tension: 0.4,
                      }
                  },

                  scales: {
                      y: {
                        border: {
                          display: false
                        },
                          grid: {
                              display: true,
                              drawTicks: false,
                              color: "rgba(255,255,255,.05)",
                              zeroLineColor: "rgba(255,255,255,.05)",
                          },
                          ticks: {
                              beginAtZero: false,
                              autoSkip: true,
                              maxTicksLimit: 4,
                              color: "#6B778C",
                              font: {
                                  size: 10,
                              }
                          }
                      },
                      x: {
                        border: {
                          display: false
                        },
                          stacked: true,
                          grid: {
                              display: false,
                              drawTicks: false,
                          },
                          ticks: {
                              beginAtZero: false,
                              autoSkip: true,
                              maxTicksLimit: 7,
                              color: "#6B778C",
                              font: {
                                  size: 10,
                              }
                          }
                      }
                  },
                  plugins: {
                      legend: {
                          display: false,
                      },
                      tooltips: {
                          backgroundColor: 'rgba(31, 59, 179, 1)',
                      },
                  }
              },
              plugins: [{
                  afterDatasetUpdate: function(chart, args, options) {
                      const chartId = chart.canvas.id;
                      var i;
                      const legendId = `${chartId}-legend`;
                      const ul = document.createElement('ul');
                      for (i = 0; i < chart.data.datasets.length; i++) {
                          ul.innerHTML += `
                <li>
                  <span style="background-color: ${chart.data.datasets[i].borderColor}"></span>
                  ${chart.data.datasets[i].label}
                </li>
              `;
                      }
                      return document.getElementById(legendId).appendChild(ul);
                  }
              }]
          });
      }

      if ($('#totalVisitors').length) {
          var bar = new ProgressBar.Circle(totalVisitors, {
              color: '#4A4C55',
              // This has to be the same size as the maximum width to
              // prevent clipping
              strokeWidth: 15,
              trailWidth: 15,
              trailColor: "#4A4C55",
              easing: 'easeInOut',
              duration: 1400,
              text: {
                  autoStyleContainer: false
              },
              from: {
                  color: '#3A61F6',
                  width: 15,
              },
              to: {
                  color: '#3A61F6',
                  width: 15
              },
              // Set default step function for all animate calls
              step: function(state, circle) {
                  circle.path.setAttribute('stroke', state.color);
                  circle.path.setAttribute('stroke-width', state.width);

                  var value = Math.round(circle.value() * 100);
                  if (value === 0) {
                      circle.setText('');
                  } else {
                      circle.setText(value);
                  }

              }
          });

          bar.text.style.fontSize = '0rem';
          bar.animate(.64); // Number from 0.0 to 1.0
      }

      if ($('#visitperday').length) {
          var bar = new ProgressBar.Circle(visitperday, {
              color: '#4A4C55',
              // This has to be the same size as the maximum width to
              // prevent clipping
              strokeWidth: 15,
              trailWidth: 15,
              trailColor: "#4A4C55",
              easing: 'easeInOut',
              duration: 1400,
              text: {
                  autoStyleContainer: false
              },
              from: {
                  color: '#04A390',
                  width: 15,
              },
              to: {
                  color: '#04A390',
                  width: 15
              },
              // Set default step function for all animate calls
              step: function(state, circle) {
                  circle.path.setAttribute('stroke', state.color);
                  circle.path.setAttribute('stroke-width', state.width);

                  var value = Math.round(circle.value() * 100);
                  if (value === 0) {
                      circle.setText('');
                  } else {
                      circle.setText(value);
                  }
              }
          });

          bar.text.style.fontSize = '0rem';
          bar.animate(.34); // Number from 0.0 to 1.0
      }

      if ($("#doughnutChart").length) {
          const doughnutChartCanvas = document.getElementById('doughnutChart');
          new Chart(doughnutChartCanvas, {
              type: 'doughnut',
              data: {
                  datasets: [{
                      data: [40, 20, 30, 10],
                      backgroundColor: [
                          "#2A4B7A",
                          "#F3C5BE",
                          "#75CDCD",
                          "#F29F67"
                      ],
                      borderColor: [
                          "#2A4B7A",
                          "#F3C5BE",
                          "#75CDCD",
                          "#F29F67"
                      ],
                  }],

                  // These labels appear in the legend and in the tooltips when hovering different arcs
                  labels: [
                      'Total',
                      'Net',
                      'Gross',
                      'AVG',
                  ]
              },
              options: {
                  cutout: 90,
                  animationEasing: "easeOutBounce",
                  animateRotate: true,
                  animateScale: false,
                  responsive: true,
                  maintainAspectRatio: true,
                  showScale: true,
                  legend: false,
                  plugins: {
                      legend: {
                          display: false,
                      }
                  }
              },
              plugins: [{
                  afterDatasetUpdate: function(chart, args, options) {
                      const chartId = chart.canvas.id;
                      var i;
                      const legendId = `${chartId}-legend`;
                      const ul = document.createElement('ul');
                      for (i = 0; i < chart.data.datasets[0].data.length; i++) {
                          ul.innerHTML += `
                <li>
                  <span style="background-color: ${chart.data.datasets[0].backgroundColor[i]}"></span>
                  ${chart.data.labels[i]}
                </li>
              `;
                      }
                      return document.getElementById(legendId).appendChild(ul);
                  }
              }]
          });
      }

      if ($("#leaveReport").length) {
          const leaveReportCanvas = document.getElementById('leaveReport');
          new Chart(leaveReportCanvas, {
              type: 'bar',
              data: {
                  labels: ["JAN", "FEB", "MAR", "APR", "MAY"],
                  datasets: [{
                      label: 'Last week',
                      data: [18, 25, 39, 11, 24],
                      backgroundColor: "#F29F67",
                      borderColor: [
                          '#F29F67',
                      ],
                      borderWidth: 0,
                      fill: true, // 3: no fill
                      barPercentage: 0.5,
                  }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    line: {
                        tension: 0.4,
                    }
                },
                scales: {
                    y: {
                        border: {
                          display: false
                        },
                        grid: {
                            display: true,
                            drawTicks: false,
                            color: "#383A42",
                            zeroLineColor: '#383A42',
                        },
                        ticks: {
                            beginAtZero: false,
                            autoSkip: true,
                            maxTicksLimit: 4,
                            color: "#808191",
                            font: {
                                size: 10,
                            }
                        }
                    },
                    x: {
                        border: {
                          display: false
                        },
                        grid: {
                            display: false,
                            drawTicks: false,
                        },
                        ticks: {
                            beginAtZero: false,
                            autoSkip: true,
                            maxTicksLimit: 7,
                            color: "#6B778C",
                            font: {
                                size: 10,
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltips: {
                        backgroundColor: 'rgba(31, 59, 179, 1)',
                    },
                }
            },

          });
      }

  });
  // iconify.load('icons.svg').then(function() {
  //   iconify(document.querySelector('.my-cool.icon'));
  // });


})(jQuery);
