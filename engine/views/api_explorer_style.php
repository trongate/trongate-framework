<style>
  :root {
    --color-get: #0285a1;
    --color-post: #a791c4;
    --color-put: #3b0a49;
    --color-delete: #6f1501;
  }

  body,
  main,
  #endpoints-tbl,
  thead th {
    color: #000;
  }

  #top-row {
    background-color: #50459b;
    color: #eee;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
  }

  #top-row>div {
    padding: 24px;
  }

  #top-row>div:nth-child(1) {
    font-size: 1.4em;
  }

  #top-rhs {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  #token-status,
  #input-token,
  #set-token-btn {
    font-size: 14px;
  }

  #top-rhs button,
  #submit-btn {
    margin: 0;
    min-width: max-content;
    background-color: #33C3F0;
    border: 1px #33C3F0 solid;
  }

  #submit-btn:hover,
  #top-rhs button:hover {
    background-color: #289ec3;
    border: 1px #289ec3 solid;
  }

  #token-status {
    min-width: max-content;
    margin-right: 12px;
  }

  #input-token {
    max-width: 22em;
    margin-right: 3px;
  }

  main .container {
    max-width: 1400px;
  }

  main h4 {
    font-size: 30px;
  }

  main h2 {
    margin: 0;
  }

  #overflow-container {
    max-width: 100%;
    margin: 0 auto 5em auto;
    overflow: auto;
  }

  #endpoints-tbl th {
    text-align: left;
  }

  tr:nth-child(odd),
  tr:nth-child(even),
  tr,
  th,
  td {
    background-color: transparent;
    border: none;
  }

  #endpoints-tbl tr {
    border-bottom: 1px #ccc solid;
  }

  #endpoints-tbl th:last-child,
  #endpoints-tbl td:last-child {
    text-align: right;
  }

  #endpoints-tbl button {
    margin: 0;
    text-transform: uppercase;
    width: 100%;
    max-width: 7em;
    font-weight: bold;
  }

  .modal#test-endpoint-modal {
    max-width: 900px;
  }

  .modal-heading {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }

  .close-modal {
    font-weight: bold;
  }

  .close-modal:hover {
    color: #ffffff;
  }

  .close-modal,
  .fa-trash {
    cursor: pointer;
  }

  .fa-trash {
    font-size: 1.2em;
  }

  .modal#test-endpoint-modal .modal-body {
    max-height: 80vh;
    overflow: auto;
  }

  .modal#test-endpoint-modal .modal-body h2 {
    margin-top: 0;
  }

  .btn-get,
  .modal-theme-get>div.modal-heading,
  .modal-theme-get>div.modal-footer {
    background-color: var(--color-get);
    border: 1px var(--color-get) solid;
  }

  .btn-post,
  .modal-theme-post>div.modal-heading,
  .modal-theme-post>div.modal-footer {
    background-color: var(--color-post);
    border: 1px var(--color-post) solid;
  }

  .btn-put,
  .modal-theme-put>div.modal-heading,
  .modal-theme-put>div.modal-footer {
    background-color: var(--color-put);
    border: 1px var(--color-put) solid;
  }

  .btn-delete,
  .modal-theme-delete>div.modal-heading,
  .modal-theme-delete>div.modal-footer {
    background-color: var(--color-delete);
    border: 1px var(--color-delete) solid;
  }

  .btn-get:hover {
    background-color: #015b74;
    border: 1px #015b74 solid;
  }

  .btn-post:hover {
    background-color: #816ea5;
    border: 1px #816ea5 solid;
  }

  .btn-put:hover {
    background-color: #290732;
    border: 1px #290732 solid;
  }

  .btn-delete:hover {
    background-color: #5a0d00;
    border: 1px #5a0d00 solid;
  }


  tr.row-get:hover,
  tr.row-post:hover,
  tr.row-put:hover,
  tr.row-delete:hover {
    background-color: rgba(255, 255, 255, 0.13);
  }

  thead tr:nth-child(odd):hover {
    background-color: transparent;
  }

  .one-col {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
  }

  .two-col {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
  }

  .two-col>div {
    width: 48%;
  }

  #http-status-code {
    text-align: right;
    margin-bottom: .4em;
    margin-right: 1em;
    font-weight: bold;
  }

  .three-col {
    width: 100%;
    display: grid;
    grid-template-columns: 3fr 3fr 4fr;
    grid-gap: 1em;
  }

  .three-col>div {
    font-size: 14px;
  }

  #header-info {
    text-align: left;
    display: none;
  }

  button#submit-btn {
    margin-top: 0;
    text-transform: uppercase;
    font-weight: 600;
  }

  .modal .submit-para,
  .modal .endpoint-details {
    text-align: left;
  }

  .endpoint-details {
    font-size: .9em;
  }

  .endpoint-details #endpoint-segments,
  .endpoint-details #request-type,
  .endpoint-details #endpoint-settings,
  .endpoint-details #token-value {
    font-weight: normal;
  }

  .modal .other-action-buttons {
    text-align: right;
    font-size: .9em;
  }

  #test-endpoint-modal>div.modal-body>form>label:nth-child(1) {
    margin-top: 3em;
  }

  label {
    font-weight: bold;
  }

  textarea {
    margin-bottom: 3em;
  }

  #params-input {
    background-color: rgb(255, 255, 255) !important;
  }

  #params-builder-tbl {
    font-size: 0.9em;
  }

  #params-builder-tbl tbody tr:hover {
    background-color: transparent;
  }

  #params-builder-tbl th {
    text-transform: uppercase;
    text-align: left;
  }

  #params-builder-tbl>tr>td:nth-child(4) {
    text-align: right;
  }

  #params-builder-tbl>thead>tr>th:nth-child(1) {
    width: 34%;
  }

  #params-builder-tbl>thead>tr>th:nth-child(2) {
    width: 12%;
  }

  #params-builder-tbl>thead>tr>th:nth-child(3) {
    width: 44%;
  }

  #params-builder-tbl>thead>tr>th:nth-child(4) {
    width: 5%;
  }

  #params-builder-tbl>tbody tr.magic-row:hover {
    background-color: #f3f3f3;
  }

  #params-builder-tbl>tbody tr.form-builder-row:hover {
    background-color: transparent;
  }

  #params-builder-tbl>tbody tr.magic-row td:hover {
    cursor: pointer;
  }

  #params-builder-tbl>tbody>tr.form-builder-row>td:nth-child(1)>select,
  #params-builder-tbl>tbody>tr.form-builder-row>td:nth-child(2)>select,
  #params-builder-tbl>tbody>tr.form-builder-row>td:nth-child(3)>input {
    background-color: #ffffff;
    font-size: 1em;
  }

  #params-builder-tbl th,
  #params-builder-tbl td {
    border: 1px #ccc solid;
  }

  #params-builder-tbl>tbody>tr.form-builder-row>td {
    text-align: center;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-gap: 1em;
    padding-bottom: 2em;
  }

  .grid-2 label {
    margin-top: 0;
  }

  #test-endpoint-modal>div.modal-body>form>div.grid-2>div:nth-child(2)>div>div:nth-child(1)>label>span {
    color: #000;
  }

  @media (max-width: 820px) {
    .three-col>div {
      font-size: .7em;
    }

    .hide-on-sm {
      display: none;
    }

  }
</style>