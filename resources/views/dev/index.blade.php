<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Executor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome/css/font-awesome.min.css">
</head>
<body>
<div class="container my-5">
    <h1 class="text-center mb-4">SQL Executor</h1>

    <div id="message" class="alert alert-info d-none"></div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="card-title">Execute SQL</h4>
            <form id="sql-form">
                <div class="mb-3">
                    <label for="sql" class="form-label">SQL Query</label>
                    <textarea class="form-control" id="sql" name="sql" rows="5"
                              placeholder="Enter your SQL query here..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Execute</button>
            </form>
        </div>
    </div>

    <div id="results-container" class="mt-4 d-none">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Results</h5>
                <table id="results-table" class="table table-striped table-bordered">
                    <thead>
                    <tr id="results-header"></tr>
                    </thead>
                    <tbody id="results-body"></tbody>
                </table>

                <!-- 分页 -->
                <div id="pagination-container" class="d-flex justify-content-center mt-3">

                </div>

                <!-- 导出 -->
                <div class="d-flex justify-content-end">
                    <form id="excel-export" class="me-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-file-excel"></i> Export to Excel
                        </button>
                    </form>
                    <form id="json-export">
                        <button type="submit" class="btn btn-info">
                            <i class="fa fa-download"></i> Export to JSON
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
<style>
    /* 自定义分页按钮的当前页样式 */
    #pagination-container button.active {
        background-color: #007bff; /* 蓝色背景 */
        color: white; /* 白色字体 */
        border-color: #007bff; /* 蓝色边框 */
    }
</style>
<!-- Bootstrap and JQuery JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function () {
        let currentPage = 1;  // 初始页码为 1
        $('#sql-form').on('submit', function (e) {
            e.preventDefault();
            const sql = $('#sql').val();
            // 显示加载消息
            $('#message').removeClass('d-none').text('正在查询...').removeClass('alert-info').addClass('alert-warning');
            // 发送 AJAX 请求获取数据
            fetchResults(sql, currentPage);
        });

        // 请求分页数据并更新结果
        function fetchResults(sql, page) {
            $.ajax({
                url: '/dev/execute',
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    sql: sql,
                    page: page  // 传递当前页码
                },
                success: function (response) {
                    if (response.error) {
                        $('#message').text('Error: ' + response.error).removeClass('alert-info alert-warning').addClass('alert-danger');
                        $('#results-container').addClass('d-none');
                    } else {
                        // 更新查询结果
                        $('#message').text('Query executed successfully!').removeClass('alert-warning').addClass('alert-success');
                        $('#results-container').removeClass('d-none');
                        displayResults(response.results);
                        console.log(response.current_page)
                        // 更新分页按钮
                        generatePagination(response.total_pages, response.current_page);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    try {
                        // 尝试解析返回的 JSON 格式错误信息
                        var response = JSON.parse(jqXHR.responseText);
                        // 检查是否包含 message 字段并显示
                        var errorMessage = response.message || '执行查询时出错';
                    } catch (e) {
                        // 如果解析失败或没有 message 字段，使用默认错误信息
                        var errorMessage = '执行查询时出错：' + errorThrown;
                    }
                    $('#message').text(errorMessage).removeClass('alert-info alert-warning').addClass('alert-danger');
                }
            });
        }

        // 在表中显示查询结果
        function displayResults(results) {
            const tableHeader = $('#results-header');
            const tableBody = $('#results-body');
            tableHeader.empty();
            tableBody.empty();

            if (results.length > 0) {
                Object.keys(results[0]).forEach(function (column) {
                    tableHeader.append('<th>' + column.charAt(0).toUpperCase() + column.slice(1) + '</th>');
                });

                // 填充
                results.forEach(function (row) {
                    let rowHtml = '<tr>';
                    Object.values(row).forEach(function (value) {
                        rowHtml += '<td>' + value + '</td>';
                    });
                    rowHtml += '</tr>';
                    tableBody.append(rowHtml);
                });
            } else {
                tableBody.append('<tr><td colspan="100%" class="text-center">No results found.</td></tr>');
            }
        }

        // 生成分页按钮
        function generatePagination(totalPages, currentPage) {
            const paginationContainer = $('#pagination-container');
            paginationContainer.empty();  // 清空当前的分页按钮

            // 生成上一页按钮
            if (currentPage > 1) {
                const prevButton = $('<button>')
                    .addClass('btn btn-secondary me-2')
                    .text('上一页')
                    .on('click', function () {
                        fetchResults($('#sql').val(), currentPage - 1);  // 请求前一页的数据
                    });
                paginationContainer.append(prevButton);
            }

            // 生成数字页码按钮
            for (let i = 1; i <= totalPages; i++) {
                let pageButton = $('<button>')
                    .addClass('btn btn-secondary me-2')
                    .text(i)
                    .attr('data-page', i)
                    .on('click', function () {
                        fetchResults($('#sql').val(), i);  // 根据点击的页码请求数据
                    });

                // 移除所有按钮的 active 类
                pageButton.removeClass('active');

                // 如果是当前页，添加 active 样式
                if (i === currentPage) {
                    pageButton.addClass('active');
                }

                paginationContainer.append(pageButton);
            }

            // 生成下一页按钮
            if (currentPage < totalPages) {
                const nextButton = $('<button>')
                    .addClass('btn btn-secondary me-2')
                    .text('下一页')
                    .on('click', function () {
                        fetchResults($('#sql').val(), currentPage + 1);  // 请求下一页的数据
                    });
                paginationContainer.append(nextButton);
            }
        }

        // 导出 Excel
        $('#excel-export').on('submit', function (e) {
            e.preventDefault();
            const sql = $('#sql').val();
            $.ajax({
                url: '/dev/export/excel',
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    sql: sql
                },
                success: function (response) {
                    if (response.file_url) {
                        window.location.href = response.file_url;
                    } else {
                        alert('导出Excel时出错: ' + response.error);
                    }
                },
                error: function () {
                    alert('导出Excel时出错。');
                }
            });
        });

        // 导出 JSON
        $('#json-export').on('submit', function (e) {
            e.preventDefault();
            const sql = $('#sql').val();
            $.ajax({
                url: '/dev/export/json',
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    sql: sql
                },
                success: function (response) {
                    if (response.file_url) {
                        // window.location.href = response.file_url;
                        // 创建一个临时的下载链接
                        const a = document.createElement('a');
                        a.href = response.file_url;
                        a.download = response.file_url.split('/').pop(); // 设置下载的文件名
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a); // 下载后移除链接
                    } else {
                        alert('导出为JSON时出错: ' + response.error);
                    }
                },
                error: function () {
                    alert('导出为JSON时出错。');
                }
            });
        });
    });
</script>

</body>
</html>
