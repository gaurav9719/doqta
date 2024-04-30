<div class="modal fade" id="updateStats" tabindex="-1" aria-labelledby="exampleModalStaticLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalStaticLabel">Update Stats</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close" onclick="closeModel()"></button>
            </div>
            <div class="modal-body" id="matchDataBody">
              <div class="p-3">

              
                <form method="post" id="updateStatsForm">
                <input  class="form-control" type="hidden" name="id" id="id">
                  
                  <div class="row">
                    <div class="col-12 mb-3">
                      <label for="exampleInputPassword1" class="form-label" >Domain</label>
                      <input type="text" class="form-control" id="domain" name="domain">
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary mt-2" id="submitBtn" >Update 
                    
                    <!-- <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"> </span> Loading... -->
                  </button>
                </form>
            </div>
            </div>
            
        </div>
    </div>
  </div>