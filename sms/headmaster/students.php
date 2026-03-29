<?php
require_once '../includes/header.php';
checkRole(['headmaster']);

$stmt = $pdo->query("
    SELECT s.*, c.class_name, u.full_name as parent_name,
           creator.full_name as creator_name, updator.full_name as updator_name
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    LEFT JOIN users u ON s.parent_id = u.id
    LEFT JOIN users creator ON s.created_by = creator.id
    LEFT JOIN users updator ON s.updated_by = updator.id
    ORDER BY c.class_name, s.full_name
");
$students = $stmt->fetchAll();
?>

<!-- Header Section -->
<div class="dash-header rounded-4 p-3 p-md-4 mb-4 border shadow-sm">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <div class="icon-box-pro">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
            <div class="min-width-0">
                <div class="d-flex align-items-center gap-2 mb-0">
                    <h4 class="fw-bold mb-0 text-primary fs-5 fs-md-4 text-truncate">All Students</h4>
                    <span class="badge border border-primary text-primary rounded-pill px-2 small d-none d-sm-inline-block bg-transparent"><?php echo count($students); ?></span>
                </div>
                <p class="text-secondary mb-0 d-none d-sm-block small text-truncate">Comprehensive student directory.</p>
            </div>
        </div>
        <div class="d-flex gap-2 ms-auto">
            <a href="javascript:history.back()" class="btn btn-body-secondary shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2 hover-translate border-0">
                <i class="bi bi-arrow-left-circle-fill text-primary fs-6"></i>
                <span class="fw-bold small text-secondary d-none d-md-inline">Go Back</span>
                <span class="fw-bold small text-secondary d-md-none">Back</span>
            </a>
        </div>
    </div>
    <div class="input-group shadow-sm rounded-4 overflow-hidden border">
        <span class="input-group-text bg-body-secondary border-0" style="width: 45px; justify-content: center;">
            <i class="bi bi-search text-primary"></i>
        </span>
        <input type="text" id="studentSearch" class="form-control border-0 py-2 py-md-3 bg-body-secondary" placeholder="Search students...">
    </div>
</div>

<div class="row mb-4 align-items-center d-none">
    <div class="col-md-8">
        <div class="d-flex align-items-center gap-3 mb-1">
            <h2 class="h4 mb-0">All Students</h2>
            <span class="badge bg-transparent border border-primary text-primary rounded-pill px-3"><?php echo count($students); ?> Total</span>
        </div>
        <p class="text-secondary small mb-0">Comprehensive list of all registered students and their details.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-body-secondary border-0 shadow-sm rounded-start-4">
                <i class="bi bi-search text-primary"></i>
            </span>
            <input type="text" id="studentSearch_old" class="form-control bg-body-secondary border-0 shadow-sm rounded-end-4" placeholder="Search students...">
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-body-secondary">
                    <tr>
                        <th class="ps-4 border-0 py-3 text-secondary small text-uppercase fw-bold">Reg No</th>
                        <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Full Name</th>
                        <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Class</th>
                        <th class="border-0 py-3 text-secondary small text-uppercase fw-bold">Parent</th>
                        <th class="pe-4 border-0 py-3 text-secondary small text-uppercase fw-bold text-end">Audit Trail</th>
                    </tr>
                </thead>
                <tbody id="studentTable">
                    <tr id="noResults" style="display: none;">
                        <td colspan="5" class="text-center py-5 text-secondary">
                            <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                            No students found matching your search.
                        </td>
                    </tr>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-secondary opacity-50 mb-3">
                                    <i class="bi bi-people display-4"></i>
                                </div>
                                <h6 class="text-secondary">No students found</h6>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="../admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none">
                                        <span class="badge border border-primary text-primary rounded-pill px-3 hover-underline bg-transparent"><?php echo $student['reg_number']; ?></span>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box-pro me-3" style="width: 32px; height: 32px; min-width: 32px;">
                                            <i class="bi bi-person fs-6"></i>
                                        </div>
                                        <a href="../admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none text-body hover-underline">
                                            <span class="fw-bold text-body"><?php echo $student['full_name']; ?></span>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-secondary small fw-bold">
                                        <i class="bi bi-mortarboard me-1"></i>
                                        <?php echo $student['class_name'] ?: 'Not Assigned'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-secondary small">
                                        <i class="bi bi-person-heart me-1"></i>
                                        <?php if ($student['parent_id']): ?>
                                            <a href="../profile.php?id=<?php echo $student['parent_id']; ?>" class="text-decoration-none text-secondary hover-underline">
                                                <?php echo $student['parent_name']; ?>
                                            </a>
                                        <?php else: ?>
                                            Not Linked
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="small text-secondary" style="font-size: 0.75rem;">
                                        <?php if ($student['creator_name']): ?>
                                            <div><i class="bi bi-plus-circle me-1"></i> Added by: 
                                                <a href="../profile.php?id=<?php echo $student['created_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                    <?php echo $student['creator_name']; ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($student['updator_name']): ?>
                                            <div class="mt-1"><i class="bi bi-pencil-circle me-1"></i> Updated by: 
                                                <a href="../profile.php?id=<?php echo $student['updated_by']; ?>" class="text-decoration-none text-secondary fw-bold hover-underline">
                                                    <?php echo $student['updator_name']; ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('studentSearch').addEventListener('input', function() {
    let filter = this.value.toLowerCase().trim();
    let rows = document.querySelectorAll('#studentTable tr:not(#noResults)');
    let hasResults = false;
    
    rows.forEach(row => {
        if(row.cells.length > 1) { // Skip possible empty state row if not already handled
            let text = row.innerText.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        }
    });
    
    let noResults = document.getElementById('noResults');
    if (noResults) {
        noResults.style.display = hasResults ? 'none' : '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
