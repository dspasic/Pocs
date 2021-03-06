<?php

define('THOUSAND_SEPARATOR', true);

class IndexView
{
    private $configuration;
    private $status;
    private $d3Scripts = array();

    public function __construct()
    {
        $this->configuration = new \Pocs\OpCache\Configuration();
        $this->status = new \Pocs\OpCache\Status();
        $this->helper = new \Pocs\View\ViewHelper();
    }

    public function pageTitle()
    {
        return 'PHP ' . PHP_VERSION . " with
            {$this->configuration['version']['opcache_product_name']}
            {$this->configuration['version']['version']}";
    }

    public function getStatus()
    {
        foreach ($this->status as $key => $value) {
            if ($key === 'scripts') {
                continue;
            }

            if (is_array($value)) {
                yield ["key" => $key, "section" => true];

                foreach ($value as $k => $v) {
                    if ($v === false) {
                        $v = 'false';
                    } elseif ($v === true) {
                        $v = 'true';
                    }
                    if ($k === 'used_memory' || $k === 'free_memory' || $k === 'wasted_memory') {
                        $v = $this->sizeForHumans(
                            $v
                        );
                    } elseif ($k === 'current_wasted_percentage' || $k === 'opcache_hit_rate') {
                        $v = number_format(
                                $v,
                                2
                            ) . '%';
                    } elseif ($k === 'blacklist_miss_ratio') {
                        $v = number_format($v, 2) . '%';
                    } elseif ($k === 'start_time' || $k === 'last_restart_time') {
                        $v = ($v ? date(DATE_RFC822, $v) : 'never');
                    }

                    if (THOUSAND_SEPARATOR === true && is_int($v)) {
                        $v = number_format($v);
                    }

                    yield ["key" => $k, "value" => $v];
                }
            } else {
                if ($value === false) {
                    $value = 'false';
                } elseif ($value === true) {
                    $value = 'true';
                }

                yield ["key" => $key, "value" => $value];
            }
        }
    }

    public function getSettings()
    {
        foreach ($this->configuration['directives'] as $key => $value) {
            if ($value === false) {
                $value = 'false';
            } elseif ($value === true) {
                $value = 'true';
            }
            if ($key == 'opcache.memory_consumption') {
                $value = $this->sizeForHumans($value);
            }
            yield ["config" => $key, "value" => $value];
        }
    }

    public function getScriptStatusRows()
    {
        foreach ($this->status['scripts'] as $key => $data) {
            $dirs[dirname($key)][basename($key)] = $data;
            $this->arrayPset($this->d3Scripts, $key, array(
                'name' => basename($key),
                'size' => $data['memory_consumption'],
            ));
        }

        asort($dirs);

        $basename = '';
        while (true) {
            if (count($this->d3Scripts) != 1) break;
            $basename .= DIRECTORY_SEPARATOR . key($this->d3Scripts);
            $this->d3Scripts = reset($this->d3Scripts);
        }

        $this->d3Scripts = $this->processPartition($this->d3Scripts, $basename);

        $id = 0;
        foreach ($dirs as $dir => $files) {
            $row = [
                'id' => ++$id,
                'count' => count($files),
                'dir' => $dir,
                'file_plural' => count($files) > 1 ? 's' : null,
                'total_memory_consumption' => \Closure::bind(function () use ($files) {
                    return $this->sizeForHumans(
                        array_sum(array_map(function($data) {
                            return $data['memory_consumption'];
                        }, $files))
                    );
                }, $this),
                'files' => \Closure::bind(function () use ($files) {
                    foreach ($files as $file => $data) {
                        $row = [
                            'file' => $file,
                            'hits' => $this->formatValue($data['hits']),
                            'memory_consumption' => $this->sizeForHumans($data['memory_consumption'])
                        ];

                        yield $row;
                    }
                }, $this),
            ];

            yield $row;
        }
    }

    public function getScriptStatusCount()
    {
        return count($this->status["scripts"]);
    }

    public function getGraphDataSetJson()
    {
        $dataset = array();
        $dataset['memory'] = array(
            $this->status['memory_usage']['used_memory'],
            $this->status['memory_usage']['free_memory'],
            $this->status['memory_usage']['wasted_memory'],
        );

        $dataset['keys'] = array(
            $this->status['opcache_statistics']['num_cached_keys'],
            $this->status['opcache_statistics']['max_cached_keys'] - $this->status['opcache_statistics']['num_cached_keys'],
            0
        );

        $dataset['hits'] = array(
            $this->status['opcache_statistics']['misses'],
            $this->status['opcache_statistics']['hits'],
            0,
        );

        $dataset['restarts'] = array(
            $this->status['opcache_statistics']['oom_restarts'],
            $this->status['opcache_statistics']['manual_restarts'],
            $this->status['opcache_statistics']['hash_restarts'],
        );

        if (THOUSAND_SEPARATOR === true) {
            $dataset['TSEP'] = 1;
        } else {
            $dataset['TSEP'] = 0;
        }

        return json_encode($dataset);
    }

    public function getHumanUsedMemory()
    {
        return $this->sizeForHumans($this->getUsedMemory());
    }

    public function getHumanFreeMemory()
    {
        return $this->sizeForHumans($this->getFreeMemory());
    }

    public function getHumanWastedMemory()
    {
        return $this->sizeForHumans($this->getWastedMemory());
    }

    public function getUsedMemory()
    {
        return $this->status['memory_usage']['used_memory'];
    }

    public function getFreeMemory()
    {
        return $this->status['memory_usage']['free_memory'];
    }

    public function getWastedMemory()
    {
        return $this->status['memory_usage']['wasted_memory'];
    }

    public function getWastedMemoryPercentage()
    {
        return number_format($this->status['memory_usage']['current_wasted_percentage'], 2);
    }

    public function getD3Scripts()
    {
        return $this->d3Scripts;
    }

    private function processPartition($value, $name = null)
    {
        if (array_key_exists('size', $value)) {
            return $value;
        }

        $array = array('name' => $name,'children' => array());

        foreach ($value as $k => $v) {
            $array['children'][] = $this->processPartition($v, $k);
        }

        return $array;
    }

    private function formatValue($value)
    {
        return $this->helper->formatNumber($value);
    }

    private function sizeForHumans($bytes)
    {
        return $this->helper->sizeForHumans($bytes);
    }

    private function arrayPset(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;
        $keys = explode(DIRECTORY_SEPARATOR, ltrim($key, DIRECTORY_SEPARATOR));
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }
}

$view = new IndexView();

ob_start();
?>
    <div class="tabs">
        <div class="tab">
            <input type="radio" id="tab-status" name="tab-group-1" checked>
            <label for="tab-status">Status</label>
            <div class="content">
                <table>
                    <?php foreach ($view->getStatus() as $row): ?>
                        <?php if (isset($row['section'])): ?>
                            <tr>
                                <th colspan="2"><?php echo $row['key'] ?></th>
                            </tr>
                       <?php else: ?>
                            <tr>
                                <th><?php echo $row['key'] ?></th>
                                <td><?php echo $row['value'] ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach ?>
                </table>
            </div>
        </div>

        <div class="tab">
            <input type="radio" id="tab-config" name="tab-group-1">
            <label for="tab-config">Configuration</label>
            <div class="content">
                <table>
                    <?php foreach ($view->getSettings() as $row): ?>
                        <tr>
                            <th><?php echo $row['config'] ?></th>
                            <td><?php echo $row['value'] ?></td>
                        </tr>
                    <?php endforeach ?>
                    </table>
            </div>
        </div>

        <div class="tab">
            <input type="radio" id="tab-scripts" name="tab-group-1">
            <label for="tab-scripts">Scripts (<?php echo $view->getScriptStatusCount(); ?>)</label>
            <div class="content">
                <table id="data-grid-scripts" style="font-size:0.8em;">
                    <tr>
                        <th width="10%">Hits</th>
                        <th width="20%">Memory</th>
                        <th width="70%">Path</th>
                    </tr>

                    <?php foreach ($view->getScriptStatusRows() as $row): ?>

                            <?php if ($row['count'] > 1): ?>
                                <tr>
                                    <th colspan="3"
                                        class="clickable"
                                        id="head-<?php echo $row['id'] ?>"
                                        data-toggle-visible="<?php echo $row['id'] ?>">
                                        <?php echo $row['dir'] . '  (' . $row['count'] . ' file' . $row['file_plural']
                                            . ' ' . $row['total_memory_consumption']() . ')' ?>
                                    </th>
                                </tr>
                            <?php endif ?>

                            <?php foreach($row['files']() as $file): ?>
                                <tr id="row-<?php echo $row['id']  ?>">
                                    <td><?php echo $file['hits'] ?></td>
                                    <td><?php echo $file['memory_consumption'] ?></td>
                                    <td><?php $row['count'] > 1 AND print $row['dir'] . '/' ?><?php echo $file['file'] ?></td>

                                </tr>
                            <?php endforeach ?>

                    <?php endforeach ?>
                </table>
            </div>
        </div>

        <div class="tab">
            <input type="radio" id="tab-visualise" name="tab-group-1">
            <label for="tab-visualise">Visualise Partition</label>
            <div class="content"></div>
        </div>

    </div>

    <div id="graph">
        <form>
            <label><input type="radio" name="dataset" value="memory" checked> Memory</label>
            <label><input type="radio" name="dataset" value="keys"> Keys</label>
            <label><input type="radio" name="dataset" value="hits"> Hits</label>
            <label><input type="radio" name="dataset" value="restarts"> Restarts</label>
        </form>

        <div id="stats"></div>
    </div>


    <div id="close-partition">&#10006; Close Visualisation</div>
    <div id="partition"></div>

    <script src="//cdnjs.cloudflare.com/ajax/libs/d3/3.0.1/d3.v3.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script>
        var dataset = <?php echo $view->getGraphDataSetJson(); ?>;

        var width = 400,
            height = 400,
            radius = Math.min(width, height) / 2,
            colours = ['#B41F1F', '#1FB437', '#ff7f0e'];

        d3.scale.customColours = function() {
            return d3.scale.ordinal().range(colours);
        };

        var colour = d3.scale.customColours();
        var pie = d3.layout.pie().sort(null);

        var arc = d3.svg.arc().innerRadius(radius - 20).outerRadius(radius - 50);
        var svg = d3.select("#graph").append("svg")
            .attr("width", width)
            .attr("height", height)
            .append("g")
            .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

        var path = svg.selectAll("path")
            .data(pie(dataset.memory))
            .enter().append("path")
            .attr("fill", function(d, i) { return colour(i); })
            .attr("d", arc)
            .each(function(d) { this._current = d; }); // store the initial values

        d3.selectAll("input").on("change", change);
        set_text("memory");

        function set_text(t) {
            if (t === "memory") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Used</th><td><?php echo $view->getHumanUsedMemory()?></td></tr>"+
                    "<tr><th style='background:#1FB437;'>Free</th><td><?php echo $view->getHumanFreeMemory()?></td></tr>"+
                    "<tr><th style='background:#ff7f0e;' rowspan=\"2\">Wasted</th><td><?php echo $view->getHumanWastedMemory()?></td></tr>"+
                    "<tr><td><?php echo $view->getWastedMemoryPercentage()?>%</td></tr></table>"
                );
            } else if (t === "keys") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Cached keys</th><td>"+format_value(dataset[t][0])+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Free Keys</th><td>"+format_value(dataset[t][1])+"</td></tr></table>"
                );
            } else if (t === "hits") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Misses</th><td>"+format_value(dataset[t][0])+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Cache Hits</th><td>"+format_value(dataset[t][1])+"</td></tr></table>"
                );
            } else if (t === "restarts") {
                d3.select("#stats").html(
                    "<table><tr><th style='background:#B41F1F;'>Memory</th><td>"+dataset[t][0]+"</td></tr>"+
                    "<tr><th style='background:#1FB437;'>Manual</th><td>"+dataset[t][1]+"</td></tr>"+
                    "<tr><th style='background:#ff7f0e;'>Keys</th><td>"+dataset[t][2]+"</td></tr></table>"
                );
            }
        }

        function change() {
            if (typeof dataset[this.value] === 'undefined') {
                return;
            }

            // Filter out any zero values to see if there is anything left
            var remove_zero_values = dataset[this.value].filter(function(value) {
                return value > 0;
            });

            // Skip if the value is undefined for some reason
            if (remove_zero_values.length > 0) {
                $('#graph').find('> svg').show();
                path = path.data(pie(dataset[this.value])); // update the data
                path.transition().duration(750).attrTween("d", arcTween); // redraw the arcs
                // Hide the graph if we can't draw it correctly, not ideal but this works
            } else {
                $('#graph').find('> svg').hide();
            }

            set_text(this.value);
        }

        function arcTween(a) {
            var i = d3.interpolate(this._current, a);
            this._current = i(0);
            return function(t) {
                return arc(i(t));
            };
        }

        function size_for_humans(bytes) {
            if (bytes > 1048576) {
                return (bytes/1048576).toFixed(2) + ' MB';
            } else if (bytes > 1024) {
                return (bytes/1024).toFixed(2) + ' KB';
            } else return bytes + ' bytes';
        }

        function format_value(value) {
            if (dataset["TSEP"] == 1) {
                return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            } else {
                return value;
            }
        }

        var w = window.innerWidth,
            h = window.innerHeight,
            x = d3.scale.linear().range([0, w]),
            y = d3.scale.linear().range([0, h]);

        var vis = d3.select("#partition")
            .style("width", w + "px")
            .style("height", h + "px")
            .append("svg:svg")
            .attr("width", w)
            .attr("height", h);

        var partition = d3.layout.partition()
            .value(function(d) { return d.size; });

        root = JSON.parse('<?php echo json_encode($view->getD3Scripts()); ?>');

        var g = vis.selectAll("g")
            .data(partition.nodes(root))
            .enter().append("svg:g")
            .attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; })
            .on("click", click);

        var kx = w / root.dx,
            ky = h / 1;

        g.append("svg:rect")
            .attr("width", root.dy * kx)
            .attr("height", function(d) { return d.dx * ky; })
            .attr("class", function(d) { return d.children ? "parent" : "child"; });

        g.append("svg:text")
            .attr("transform", transform)
            .attr("dy", ".35em")
            .style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; })
            .text(function(d) { return d.name; })

        d3.select(window)
            .on("click", function() { click(root); })

        function click(d) {
            if (!d.children) return;

            kx = (d.y ? w - 40 : w) / (1 - d.y);
            ky = h / d.dx;
            x.domain([d.y, 1]).range([d.y ? 40 : 0, w]);
            y.domain([d.x, d.x + d.dx]);

            var t = g.transition()
                .duration(d3.event.altKey ? 7500 : 750)
                .attr("transform", function(d) { return "translate(" + x(d.y) + "," + y(d.x) + ")"; });

            t.select("rect")
                .attr("width", d.dy * kx)
                .attr("height", function(d) { return d.dx * ky; });

            t.select("text")
                .attr("transform", transform)
                .style("opacity", function(d) { return d.dx * ky > 12 ? 1 : 0; });

            d3.event.stopPropagation();
        }

        function transform(d) {
            return "translate(8," + d.dx * ky / 2 + ")";
        }

        $(document).ready(function() {
            function handleVisualisationToggle(close) {
                $('#partition, #close-partition').fadeToggle();
                // Is the visualisation being closed? If so show the status tab again
                if (close) {
                    $('#tab-visualise').removeAttr('checked');
                    $('#tab-status').trigger('click');
                }
            }

            $('label[for="tab-visualise"], #close-partition').on('click', function() {
                handleVisualisationToggle(($(this).attr('id') === 'close-partition'));
            });

            $(document).keyup(function(e) {
                if (e.keyCode == 27) handleVisualisationToggle(true);
            });

            var hidden = {};
            function toggleVisible(head, row) {
                if (!hidden[row]) {
                    d3.selectAll(row).transition().style('display', 'none');
                    hidden[row] = true;
                    d3.select(head).transition().style('color', '#ccc');
                } else {
                    d3.selectAll(row).transition().style('display');
                    hidden[row] = false;
                    d3.select(head).transition().style('color', '#000');
                }
            }

            $('th[data-toggle-visible]', '#data-grid-scripts').on('click', function () {
                var id = $(this).data('toggle-visible');
                toggleVisible('#head-' + id, '#row-' + id);
            });
        });
    </script>
<?php
$content = ob_get_clean();

include __DIR__ . '/layout.php';
