/**
 * Progress Section Format
 *
 * @package    course/format
 * @subpackage vsf
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  2016 Gareth J Barnard
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    Chartist is MIT licenced: https://raw.githubusercontent.com/gionkunz/chartist-js/master/LICENSE-MIT
 */

/* jshint ignore:start */
define(['jquery', 'format_vsf/chartist', 'core/log'], function($, Chartist, log) {

    "use strict"; // jshint ;_;

    log.debug('Progress Section Format Chartist AMD jQuery initialised');

    /**
     * Chartist.js plugin to pre fill donouts with animations.
     * author: moxx
     * author-url: https://github.com/moxx/chartist-plugin-fill-donut
     * @license    None specified.  Code is public.
     */
    (function (document, Chartist) {
        'use strict';

        var defaultOptions = {
            fillClass: 'ct-fill-donut',
            label: {
                html: 'div',
                class: 'ct-fill-donut-label'
            },
            items: [{}]
        };

        Chartist.plugins = Chartist.plugins || {};
        Chartist.plugins.fillDonut = function (options) {
            options = Chartist.extend({}, defaultOptions, options);
            return function fillDonut(chart) {
                if (chart instanceof Chartist.Pie) {
                    var $chart = chart.container;
                    $chart.style.position = 'relative';
                    var $svg;

                    var drawDonut = function(data) {
                        if (data.type === 'slice') {
                            if (data.index === 0) {
                                $svg = $chart.querySelector('svg');
                            }

                            var $clone = data.group._node.cloneNode(true);
                            options.fillClass.split(" ").forEach(function (className) {
                                $clone.setAttribute('class', $clone.getAttribute('class') + ' ' + className);
                            });

                            [].forEach.call($clone.querySelectorAll('path'), function (el) {
                                [].forEach.call(el.querySelectorAll('animate'), function (node) {
                                    node.parentNode.removeChild(node);
                                });

                                el.removeAttribute('stroke-dashoffset');
                            });

                            $svg.insertBefore($clone, $svg.childNodes[0]);

                        }
                    };

                    chart.on('draw', function (data) {
                        drawDonut(data);
                    });

                    chart.on('created', function (data) {
                        var itemIndex = 0;

                        if (chart.options.fillDonutOptions) {
                            options = Chartist.extend({}, options, chart.options.fillDonutOptions);
                            drawDonut(data);
                        }

                        [].forEach.call(options.items, function (thisItem) {
                            var $wrapper = document.createElement(options.label.html);
                            options.label.class.split(" ").forEach(function (className) {
                                if ($wrapper.classList) {
                                    $wrapper.classList.add(className);
                                } else {
                                    $wrapper.className += ' ' + className;
                                }
                            });
                            var item = Chartist.extend({}, {
                                class: '',
                                id: '',
                                content: 'fillText',
                                position: 'center', //bottom, top, left, right
                                offsetY: 0, //top, bottom in px
                                offsetX: 0 //left, right in px
                            }, thisItem);


                            if (item.id.length > 0) {
                                $wrapper.setAttribute('id', item.id);
                            }
                            if (item.class.length > 0) {
                                $wrapper.setAttribute('class', item.class);
                            }

                            [].forEach.call($chart.querySelectorAll('*[data-fill-index$="fdid-' + itemIndex + '"]'), function (node) {
                                node.parentNode.removeChild(node);
                            });
                            $wrapper.setAttribute('data-fill-index', 'fdid-' + itemIndex);
                            itemIndex += 1;

                            $wrapper.insertAdjacentHTML('beforeend', item.content);
                            $wrapper.style.position = 'absolute';
                            $chart.appendChild($wrapper);

                            var cWidth = Math.ceil($chart.offsetWidth / 2);
                            var cHeight = Math.ceil($chart.clientHeight / 2);
                            var wWidth = Math.ceil($wrapper.offsetWidth / 2);
                            var wHeight = Math.ceil($wrapper.clientHeight / 2);

                            var style = {
                                bottom: {
                                    bottom: 0 + item.offsetY + "px",
                                    left: (cWidth - wWidth) + item.offsetX + "px"
                                },
                                top: {
                                    top: 0 + item.offsetY + "px",
                                    left: (cWidth - wWidth) + item.offsetX + "px"
                                },
                                left: {
                                    top: (cHeight - wHeight) + item.offsetY + "px",
                                    left: 0 + item.offsetX + "px"
                                },
                                right: {
                                    top: (cHeight - wHeight) + item.offsetY + "px",
                                    right: 0 + item.offsetX + "px"
                                },
                                center: {
                                    top: (cHeight - wHeight) + item.offsetY + "px",
                                    left: (cWidth - wWidth) + item.offsetX + "px"
                                }
                            };

                            Chartist.extend($wrapper.style, style[item.position]);
                        });
                    });
                }
            };
        };
    }(document, Chartist));

    return {
        init: function(data) {
            log.debug('Progress Section Format Chartist AMD init initialised');
            log.debug(data);

            function process_chart(data, index) {
                var options = {
                    donut: true,
                    donutWidth: 10,
                    startAngle: 0,
                    total: 100,
                    showLabel: false,
                    plugins: [
                        // https://github.com/moxx/chartist-plugin-fill-donut#license.
                        Chartist.plugins.fillDonut({
                            items: [{
                                content: '<p class="vsf-percentage">' + data.chartdata.series[0] +'%</p>'
                            }],
                            label : {
                                html: '<div></div>',
                                class: 'ct-fill-donut-label'
                            }
                        })
                    ]
                };
                var chart = new Chartist.Pie('.vsf-chart-section-' + data.sectionno, data.chartdata, options);

                chart.on('draw', function(data) {
                    if(data.type === 'slice' && data.index == 0) {
                        var node = data.element._node;
                        var len = node.getTotalLength();
                        node.style.transition = 'none';
                        node.style.WebkitTransition = node.style.transition;
                        node.style.strokeDasharray = len + 'px ' + len + 'px';
                        node.style.strokeDashoffset = -len + 'px';
                        node.getBoundingClientRect();
                        node.style.transition = 'stroke-dashoffset 2s ease-out';
                        node.style.WebkitTransition = node.style.transition;
                        node.style.strokeDashoffset = '0px';
                    }
                });
            }

            $(document).ready(function() {
                data.forEach(process_chart);
            });
        }
    }
});
/* jshint ignore:end */
