<script>
const trongatePagesObj = {
  baseUrl: '<?php

declare(strict_types=1);

  echo BASE_URL ?>',
  trongatePagesId: '<?php echo $recordId ?>',
  trongatePagesToken: '<?php echo $trongate_token ?>',
  imgRootDir: '../modules/trongate_pages/assets/images/uploads',
  currentImgDir: '',
  pageBody: document.getElementsByTagName('body')[0],
  defaultActiveElParent: document.getElementsByClassName('page-content')[0],
  headlineTags: ["H1", "H2", "H3", "H4", "H5", "h1", "h2", "h3", "h4", "h5"],
  targetTable: '<?php echo $targetTable ?>',
  imgUploadApi: '<?php echo $imgUploadApi ?>',
  moduleAssetsTrigger: '<?php echo MODULE_ASSETS_TRIGGER ?>',
  activeElParent: document.getElementsByClassName('page-content')[0],
  activeEl: document.getElementsByClassName('page-content')[0],
  currentlySelectedElType: '',
  targetNewElLocation: '',
  editorDock: {},
  lastHeadlineSelected: {},
  storedRange: null,
  targetVideoDiv: null,
  textDivSampleText: '<?php echo $sample_text ?>'
}

const tgpScriptUrls = [
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/button_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/camera_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/code_view.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/divider_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/dock_manager.js', 
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/element_adder_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/folder_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/headline_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/image_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/text_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/trongate_pages.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/toolbar_manager.js',
 '<?php echo BASE_URL ?>trongate_pages<?php echo MODULE_ASSETS_TRIGGER ?>/js/youtube_manager.js'
];

const tgpModals = [
          'tgp-button-modal',
          'tgp-camera-modal',
          'tgp-code-view-modal',
          'tgp-conf-trashify-modal',
          'tgp-confirm-save-page',
          'tgp-create-page-el',
          'tgp-delete-page-modal',
          'tgp-image-modal',
          'tgp-intercept-add-el',
          'tgp-link-modal',
          'tgp-media-manager',
          'tgp-mobi-options',
          'tgp-settings-modal',
          'tgp-video-overlay',
          'tgp-youtube-modal'
        ];

function tgpLoadScripts(urls) {
  const promises = urls.map(url => {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.type = 'text/javascript';
      script.async = true;
      script.onload = () => {
        resolve();
      };
      script.onerror = () => {
        reject(`Failed to load script: ${url}`);
      };
      script.src = url;
      document.head.appendChild(script);
    });
  });

  return Promise.all(promises);
}

window.onload = (event) => {
    tgpLoadScripts(tgpScriptUrls)
      .then(() => {
        tgpStartPageEditor(); // declared on trongate_pages.js
      })
      .catch(error => {
        console.error(error);
      });
};
</script>