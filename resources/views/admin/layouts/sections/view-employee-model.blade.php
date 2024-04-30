<div class="modal fade" id="viewMatch" tabindex="-1" aria-labelledby="exampleModalStaticLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalStaticLabel">Employees</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close" onclick="closeModel()"></button>
            </div>
            <div class="modal-body" id="matchDataBody">
            <table class="table">
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Name</th>
                  <th scope="col">Corporate Email</th>
                  <th scope="col">Status</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody >
                <tr class="">
                  <th colspan="5">No Record Found</th>
                </tr>
                
              </tbody>
            </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-text-primary me-2" type="button" data-bs-dismiss="modal" onclick="closeModel()">Close</button>
            </div>
        </div>
    </div>
  </div>