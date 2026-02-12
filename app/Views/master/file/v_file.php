<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header dflex align-center justify-end">
            <div class="d-flex align-items-end ms-auto gap-2">
                <button type="button" id="btnAddNew" class="btn btn-primary btn-sm"
                    onclick="window.location.href='<?= site_url('file/form') ?>'">
                    <i class="bx bx-plus-circle"></i> Add New
                </button>
            </div>
            <div class="table-responsive margin-t-14p">
                <table id="fileTable" class="table table-bordered table-master fs-7 w-100">
                    <thead>
                        <tr>
                            <td class="tableheader">No</td>
                            <td class="tableheader">File Name</td>
                            <td class="tableheader">File Directory</td>
                            <td class="tableheader">Created At</td>
                            <td class="tableheader">Created By</td>
                            <td class="tableheader">Actions</td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->include('template/v_footer') ?>
<script>
    $(document).ready(function() {
        initDataTable();
    });

    function initDataTable() {
        tbl = $('#fileTable').DataTable({
            serverSide: true,
            processing: true,
            destroy: true,
            ajax: {
                url: '<?= base_url("file/table") ?>',
                type: 'POST',
            },
            columns: [{
                    data: 0
                },
                {
                    data: 1
                },
                {
                    data: 2
                },
                {
                    data: 3
                },
                {
                    data: 4
                },
                {
                    data: 5
                }
            ]

        });
    }
</script>