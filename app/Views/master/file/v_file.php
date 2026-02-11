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
            <div class="card-body">
                <div class="table-responsive margin-t-14p">
                    <table class="table table-bordered table-responsive-lg table-master fs-7 w-100">
                        <thead>
                            <tr>
                                <td class="tableheader">No</td>
                                <td class="tableheader">File Name</td>
                                <td class="tableheader">Description</td>
                                <td class="tableheader">Actions</td>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->include('template/v_footer') ?>