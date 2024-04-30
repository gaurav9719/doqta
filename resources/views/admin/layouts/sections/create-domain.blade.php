<div class="modal fade" id="createStats" tabindex="-1" aria-labelledby="exampleModalStaticLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalStaticLabel">Add Domain</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close" onclick="closeModel()"></button>
            </div>
            <div class="modal-body" id="matchDataBody">
              <div class="p-3">

              
                <form method="post" id="createStatsForm">
                  <div class="row">
                    <div class="col-12 mb-3">
                      <label for="exampleInputPassword1" class="form-label" >Add Domain</label>
                      <input type="text" class="form-control" name="domain">
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary mt-2" id="submitBtn" >Submit 
                    
                    <!-- <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"> </span> Loading... -->
                  </button>
                </form>
            </div>
            </div>
            
        </div>
    </div>
  </div>