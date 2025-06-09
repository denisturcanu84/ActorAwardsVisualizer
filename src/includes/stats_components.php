<?php
function renderStatsSection($title, $description, $chartId, $exportType, $tableData, $columns) {
    ?>
    <div class="stats-section">
        <div class="section-header">
            <div class="section-title">
                <h2><?php echo htmlspecialchars($title); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars($description); ?></p>
            </div>
            <div class="export-wrapper">
                <button class="export-button">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                <div class="export-dropdown">
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=csv" class="export-option" download>
                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=webp" class="export-option" download>
                        <i class="fas fa-image"></i>
                        WebP
                    </a>
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=svg" class="export-option" download>
                        <i class="fas fa-bezier-curve"></i>
                        SVG
                    </a>
                </div>
            </div>
        </div>
        <div class="chart-and-table">
            <div class="chart-wrapper">
                <canvas id="<?php echo $chartId; ?>"></canvas>
            </div>
            <div class="table-wrapper collapsed">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?php echo htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableData as $row): ?>
                        <tr>
                            <?php foreach ($columns as $key => $column): ?>
                                <td>
                                    <?php 
                                    $value = $row[array_keys($row)[$key]] ?? '';
                                    if ($key === count($columns) - 1 && str_contains($column, 'Rate')) {
                                        echo $value . '%';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="show-more-container">
                <button class="show-more-btn">
                    Show More
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    <?php
}

function renderPerformersList($title, $description, $exportType, $performers) {
    ?>
    <div class="performers-section">
        <div class="section-header">
            <div class="section-title">
                <h2><?php echo htmlspecialchars($title); ?></h2>
                <p class="section-description"><?php echo htmlspecialchars($description); ?></p>
            </div>
            <?php if ($exportType): ?>
            <div class="export-wrapper">
                <button class="export-button">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                <div class="export-dropdown">
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=csv" class="export-option" download>
                        <i class="fas fa-file-csv"></i>
                        CSV
                    </a>
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=webp" class="export-option" download>
                        <i class="fas fa-image"></i>
                        WebP
                    </a>
                    <a href="/pages/stats.php?export=<?php echo $exportType; ?>&format=svg" class="export-option" download>
                        <i class="fas fa-bezier-curve"></i>
                        SVG
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="performers-container">
            <div class="performer-list">
                <?php foreach ($performers as $index => $performer): ?>
                <div class="performer-item">
                    <span class="performer-rank"><?php echo $index + 1; ?></span>
                    <div class="performer-image">
                        <?php if ($performer['image_url']): ?>
                            <img src="https://image.tmdb.org/t/p/w185<?php echo htmlspecialchars($performer['image_url']); ?>" alt="<?php echo htmlspecialchars($performer['name'] ?? $performer['title']); ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="performer-info">
                        <h4 class="performer-name"><?php echo htmlspecialchars($performer['name'] ?? $performer['title']); ?></h4>
                        <div class="performer-stats">
                            <span class="wins"><?php echo $performer['wins']; ?> wins</span>
                            <span class="separator">•</span>
                            <span class="nominations"><?php echo $performer['nominations']; ?> nominations</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
?>