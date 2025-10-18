/**
 * Template Builder - Versión actualizada para Timber/Twig
 * Compatible con la implementación donde sections[item].section_id determina el template Twig
 */

(function ($) {
  "use strict";

  // Datos de la aplicación
  let templates = [];
  let selectedTemplate = null;
  let availableSections = [];
  let sectionSchemas = {};
  let loading = true;
  let currentMessage = null;
  let expandedSections = {};
  let activeTabs = {};

  // URL base para AJAX
  const ajaxUrl = sectionsBuilderData.ajaxUrl;
  const nonce = sectionsBuilderData.nonce;
  const timberEnabled = sectionsBuilderData.timberEnabled || false;

  // Elementos DOM
  let $app;
  let $templatesList;
  let $sectionsList;
  let $editor;
  let $message;
  let $loading;
  let $saveButton;
  let $templateTitle;
  let $builder;

  // Inicializar la aplicación
  function init() {
    createAppStructure();
    loadTemplates();
    loadAvailableSections();
    loadSectionSchemas();
    registerEvents();
    registerBlockEvents(); // Añadir esta línea
  }

  // Crear la estructura básica de la aplicación
  function createAppStructure() {
    $app = $("#sections-builder-app");

    const timberStatus = timberEnabled
      ? '<div class="sb-timber-status sb-timber-enabled">✓ Timber/Twig habilitado</div>'
      : '<div class="sb-timber-status sb-timber-disabled">⚠ Timber/Twig no detectado</div>';

    const structure = `
          <div id="sb-message" class="sb-message" style="display: none;"></div>
          
          <div id="sb-loading" class="sb-loading">
              <p>Cargando Template Builder...</p>
              ${timberStatus}
          </div>
          
          <div id="sb-builder" class="sb-builder" style="display: none;">
              <div class="sb-sidebar">
                  <div class="sb-panel">
                      <h3 class="sb-panel-title">Plantillas Disponibles</h3>
                      <div class="sb-panel-content">
                          <ul id="sb-templates-list" class="sb-list"></ul>
                          <div style="margin-top: 15px">
                              <button id="sb-new-template" class="sb-button sb-button-primary">
                                  Nueva Plantilla
                              </button>
                          </div>
                      </div>
                  </div>
                  
                  <div class="sb-panel">
                      <h3 class="sb-panel-title">Secciones Twig Disponibles</h3>
                      <div class="sb-panel-content">
                          <ul id="sb-sections-list" class="sb-list"></ul>
                          <div class="sb-sections-info">
                              <small class="sb-help-text">
                                  Templates encontrados en <code>views/sections/</code><br>
                                  ⚙️ = Con esquema | 📄 = Sin esquema
                              </small>
                          </div>
                      </div>
                  </div>
              </div>
              
              <div class="sb-content">
                  <div class="sb-header">
                      <h2 id="sb-template-title">Template Builder - Timber/Twig</h2>
                      <div>
                          <button id="sb-save-template" class="sb-button sb-button-primary" style="display: none;">
                              Guardar Plantilla
                          </button>
                      </div>
                  </div>
                  
                  <div id="sb-editor" class="sb-editor"></div>
              </div>
          </div>
      `;

    $app.html(structure);

    // Guardar referencias a elementos
    $templatesList = $("#sb-templates-list");
    $sectionsList = $("#sb-sections-list");
    $editor = $("#sb-editor");
    $message = $("#sb-message");
    $loading = $("#sb-loading");
    $saveButton = $("#sb-save-template");
    $templateTitle = $("#sb-template-title");
    $builder = $("#sb-builder");

    // Añadir estilos específicos para Timber
    addTimberStyles();
  }

  // Añadir estilos CSS específicos para la versión Timber
  function addTimberStyles() {
    if ($("#sb-timber-styles").length) return;

    $("head").append(`
          <style id="sb-timber-styles">
              .sb-timber-status {
                  padding: 8px 12px;
                  border-radius: 4px;
                  font-size: 12px;
                  margin-top: 10px;
                  text-align: center;
              }
              .sb-timber-enabled {
                  background: #d4edda;
                  color: #155724;
                  border: 1px solid #c3e6cb;
              }
              .sb-timber-disabled {
                  background: #fff3cd;
                  color: #856404;
                  border: 1px solid #ffeaa7;
              }
              .sb-section-indicator {
                  margin-right: 8px;
                  font-size: 14px;
              }
              .sb-section-meta {
                  font-size: 11px;
                  color: #666;
                  margin-top: 2px;
              }
              .sb-section-schema-info {
                  display: inline-block;
                  background: #f8f9fa;
                  padding: 2px 6px;
                  border-radius: 3px;
                  font-size: 10px;
              }
              .sb-timber-info {
                  background: #e8f4f8;
                  border: 1px solid #bee5eb;
                  border-radius: 4px;
                  padding: 10px;
                  margin: 10px 0;
              }
              .sb-timber-info h4 {
                  margin: 0 0 8px 0;
                  color: #0c5460;
                  font-size: 13px;
              }
              .sb-timber-info ul {
                  margin: 0;
                  padding-left: 20px;
                  font-size: 12px;
              }
              .sb-template-path {
                  font-family: monospace;
                  background: #f8f9fa;
                  padding: 2px 4px;
                  border-radius: 2px;
                  font-size: 11px;
              }
          </style>
      `);
  }

  // Función para renderizar configuración de un bloque
  function renderBlockSettings(
    sectionKey,
    blockIndex,
    block,
    blockDefinition,
    allBlocksDefinition
  ) {
    const blockType = block.type || block.block_id || "default";
    const blockProperties = blockDefinition.properties || {};
    const blockName = blockDefinition.name || "Bloque";

    let html = `
        <div class="sb-block" data-section="${sectionKey}" data-block-index="${blockIndex}">
            <div class="sb-block-header">
                <h5 class="sb-block-title">
                    <span class="sb-block-icon">🧱</span>
                    ${blockName}
                    <small class="sb-block-type">(${blockType})</small>
                </h5>
                <div class="sb-block-actions">
                    <button class="sb-button sb-button-secondary sb-button-sm sb-move-block-up" title="Subir bloque">↑</button>
                    <button class="sb-button sb-button-secondary sb-button-sm sb-move-block-down" title="Bajar bloque">↓</button>
                    <select class="sb-block-type-selector" data-section="${sectionKey}" data-block-index="${blockIndex}">
                        ${Object.entries(allBlocksDefinition)
                          .map(
                            ([id, def]) =>
                              `<option value="${id}" ${
                                id === blockType ? "selected" : ""
                              }>${def.name}</option>`
                          )
                          .join("")}
                    </select>
                    <button class="sb-button sb-button-danger sb-button-sm sb-remove-block" title="Eliminar bloque">×</button>
                </div>
            </div>
            <div class="sb-block-content">
    `;

    // Renderizar campos del bloque
    if (Object.keys(blockProperties).length > 0) {
      Object.entries(blockProperties).forEach(([key, property]) => {
        const value =
          block.settings && block.settings[key] !== undefined
            ? block.settings[key]
            : property.default || "";
        html += renderSchemaField(
          sectionKey,
          key,
          property,
          value,
          "block",
          blockIndex
        );
      });
    } else {
      html +=
        '<p class="sb-form-help">Este bloque no tiene campos configurables definidos.</p>';
    }

    html += `
            <p class="sb-form-help"><strong>Acceso en Twig:</strong> <code>{{ block.settings.campo }}</code> dentro del loop de bloques</p>
            </div>
        </div>
    `;

    return html;
  }

  // Registrar eventos
  function registerEvents() {
    $("#sb-new-template").on("click", createNewTemplate);
    $("#sb-save-template").on("click", saveTemplate);

    // Eventos para plantillas
    $templatesList.on("click", "li.sb-list-item", function () {
      const templateId = $(this).data("id");
      console.log("Plantilla seleccionada:", templateId);
      loadTemplate(templateId);
    });

    // Eventos para secciones
    $sectionsList.on("click", "li.sb-list-item", function () {
      const sectionId = $(this).data("id");
      addSection(sectionId);
    });

    // Eventos para el editor
    $editor.on("click", ".sb-section-header", function () {
      const sectionId = $(this).closest(".sb-section").data("id");
      toggleSection(sectionId);
    });

    $editor.on("click", ".sb-remove-section", function (e) {
      e.stopPropagation();
      const sectionId = $(this).closest(".sb-section").data("id");
      removeSection(sectionId);
    });

    $editor.on("click", ".sb-move-up", function (e) {
      e.stopPropagation();
      const sectionId = $(this).closest(".sb-section").data("id");
      moveSectionUp(sectionId);
    });

    $editor.on("click", ".sb-move-down", function (e) {
      e.stopPropagation();
      const sectionId = $(this).closest(".sb-section").data("id");
      moveSectionDown(sectionId);
    });

    // Actualizar configuración de sección (ahora va directamente a la sección, no a settings)
    $editor.on("change", ".sb-setting-input", function () {
      const $this = $(this);
      const sectionKey = $this.closest(".sb-section").data("id");
      const setting = $this.data("setting");
      let value = $this.val();

      // Convertir según el tipo de dato
      const type = $this.data("type");
      if (type === "number") {
        value = parseFloat(value);
      } else if (type === "boolean") {
        value = $this.is(":checked");
      } else if (type === "json") {
        try {
          value = JSON.parse(value);
        } catch (error) {
          showMessage("error", "JSON inválido: " + error.message);
          return;
        }
      }

      updateSectionSetting(sectionKey, setting, value);
    });

    // Actualizar datos de plantilla
    $editor.on("change", ".sb-template-input", function () {
      const $this = $(this);
      const field = $this.data("field");
      const value = $this.val();

      updateTemplateField(field, value);
    });

    // Selector de imágenes
    $editor.on("click", ".sb-select-image", function (e) {
      e.preventDefault();
      selectWordPressImage($(this));
    });

    $editor.on("click", ".sb-remove-image", function (e) {
      e.preventDefault();
      removeSelectedImage($(this));
    });
  }

  // Cargar plantillas
  async function loadTemplates() {
    setLoading(true);

    try {
      const response = await jQuery.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          action: "get_templates",
          nonce: nonce,
        },
      });

      if (response.success) {
        templates = response.data;
        console.log("Plantillas cargadas:", templates);
        renderTemplatesList();
        setLoading(false);
      } else {
        throw new Error("Error al cargar las plantillas");
      }
    } catch (error) {
      console.error("Error:", error);
      showMessage("error", error.message);
      setLoading(false);
    }
  }

  // Cargar secciones disponibles (ahora templates Twig)
  async function loadAvailableSections() {
    try {
      const response = await jQuery.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          action: "get_sections",
          nonce: nonce,
        },
      });

      if (response.success) {
        availableSections = response.data;
        console.log("Secciones Twig disponibles:", availableSections);
        renderSectionsList();
      } else {
        throw new Error("Error al cargar las secciones disponibles");
      }
    } catch (error) {
      console.error("Error:", error);
      showMessage("error", error.message);
    }
  }

  // Cargar esquemas de secciones
  async function loadSectionSchemas() {
    try {
      const response = await jQuery.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          action: "get_section_schemas",
          nonce: nonce,
        },
      });

      if (response.success) {
        sectionSchemas = response.data;
        console.log("Esquemas de secciones:", sectionSchemas);
      } else {
        throw new Error("Error al cargar los esquemas de secciones");
      }
    } catch (error) {
      console.error("Error:", error);
      showMessage("error", error.message);
    }
  }

  // Cargar plantilla específica
  async function loadTemplate(templateName) {
    setLoading(true);

    try {
      console.log("Cargando plantilla específica:", templateName);

      const response = await jQuery.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          action: "get_template",
          nonce: nonce,
          template_name: templateName,
        },
      });

      if (response.success) {
        selectedTemplate = response.data;
        selectedTemplate._originalId = templateName;

        // Asegurar estructura compatible con Timber/Twig
        ensureTimberStructure();

        console.log("Plantilla cargada (Timber):", selectedTemplate);

        updateActiveTemplate();
        initExpandedSections();
        renderTemplateEditor();
        setLoading(false);
      } else {
        throw new Error(
          response.data?.message || "Error al cargar la plantilla"
        );
      }
    } catch (error) {
      console.error("Error al cargar plantilla:", error);
      showMessage("error", error.message);
      setLoading(false);
    }
  }

  // Asegurar estructura correcta para Timber/Twig
  function ensureTimberStructure() {
    if (!selectedTemplate) return;

    // Asegurar que existe order
    if (!selectedTemplate.order || !Array.isArray(selectedTemplate.order)) {
      selectedTemplate.order = Object.keys(selectedTemplate.sections || {});
    }

    // Asegurar que existe sections
    if (
      !selectedTemplate.sections ||
      typeof selectedTemplate.sections !== "object"
    ) {
      selectedTemplate.sections = {};
    }

    // Asegurar que cada sección tiene section_id
    Object.keys(selectedTemplate.sections).forEach((sectionKey) => {
      const section = selectedTemplate.sections[sectionKey];
      if (!section.section_id) {
        // Intentar extraer el section_id del key
        section.section_id = sectionKey.replace(/^section_\d+_/, "");
      }
    });
  }

  // Guardar plantilla
  async function saveTemplate() {
    if (!selectedTemplate) {
      showMessage("error", "No hay una plantilla seleccionada");
      return;
    }

    if (!selectedTemplate._originalId) {
      const templateName = prompt("Ingresa un nombre único para la plantilla:");
      if (!templateName) return;

      const validNameRegex = /^[a-zA-Z0-9_-]+$/;
      if (!validNameRegex.test(templateName)) {
        showMessage(
          "error",
          "Nombre inválido. Usa solo letras, números, guiones y guiones bajos."
        );
        return;
      }

      selectedTemplate._originalId = templateName;
    }

    setLoading(true);

    try {
      const templateName = selectedTemplate._originalId;
      console.log("Guardando plantilla Timber:", templateName);

      // Crear copia sin referencias internas
      const templateCopy = JSON.parse(JSON.stringify(selectedTemplate));
      delete templateCopy._originalId;

      console.log("Estructura a guardar:", templateCopy);

      const response = await jQuery.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          action: "save_template",
          nonce: nonce,
          template_name: templateName,
          template_data: JSON.stringify(templateCopy),
        },
      });

      if (response.success) {
        showMessage(
          "success",
          `Plantilla "${templateName}" guardada correctamente`
        );
        selectedTemplate._originalId = templateName;
        await loadTemplates();
        setLoading(false);
      } else {
        throw new Error(
          response.data?.message || "Error al guardar la plantilla"
        );
      }
    } catch (error) {
      console.error("Error al guardar:", error);
      showMessage("error", error.message);
      setLoading(false);
    }
  }

  // Crear nueva plantilla
  function createNewTemplate() {
    const templateName = prompt("Ingresa un nombre para la nueva plantilla:");
    if (!templateName) return;

    const validNameRegex = /^[a-zA-Z0-9_-]+$/;
    if (!validNameRegex.test(templateName)) {
      showMessage(
        "error",
        "Nombre inválido. Usa solo letras, números, guiones y guiones bajos."
      );
      return;
    }

    // Crear estructura compatible con Timber/Twig
    selectedTemplate = {
      name: templateName,
      description: "Plantilla creada con Template Builder para Timber/Twig",
      template: true,
      order: [], // Orden de las secciones
      sections: {}, // Secciones con configuraciones
      _originalId: templateName,
    };

    console.log("Nueva plantilla Timber creada:", selectedTemplate);

    updateActiveTemplate();
    initExpandedSections();
    renderTemplateEditor();

    showMessage(
      "success",
      `Nueva plantilla "${templateName}" creada. No olvides guardarla.`
    );
  }

  // Añadir sección a la plantilla
  function addSection(sectionId) {
    if (!selectedTemplate) {
      showMessage("error", "No hay una plantilla seleccionada");
      return;
    }

    // Generar ID único para la sección
    const uniqueId = `section_${Date.now()}`;

    // Crear nueva sección con estructura para Timber/Twig
    selectedTemplate.sections[uniqueId] = {
      section_id: sectionId, // Este es el que determina qué template Twig usar
      // Las configuraciones van directamente aquí, no dentro de 'settings'
    };

    // Añadir al orden
    selectedTemplate.order.push(uniqueId);

    // Expandir la nueva sección
    expandedSections[uniqueId] = true;

    renderTemplateEditor();

    const sectionName = availableSections[sectionId]?.name || sectionId;
    showMessage("success", `Sección "${sectionName}" añadida`);
  }

  // Renderizar lista de plantillas
  function renderTemplatesList() {
    $templatesList.empty();

    if (!templates || Object.keys(templates).length === 0) {
      $templatesList.html(
        '<div class="sb-empty">No hay plantillas disponibles</div>'
      );
      return;
    }

    Object.entries(templates).forEach(([id, template]) => {
      const name = template.name || id;
      const sectionsCount = template.sections_count || 0;

      const $item = $(`
              <li class="sb-list-item" data-id="${id}">
                  <div class="sb-template-info">
                      <div class="sb-template-name">${name}</div>
                      <div class="sb-template-meta">
                          ${sectionsCount} secciones
                      </div>
                  </div>
              </li>
          `);

      if (selectedTemplate && id === selectedTemplate._originalId) {
        $item.addClass("active");
      }

      $templatesList.append($item);
    });
  }

  // Renderizar lista de secciones
  function renderSectionsList() {
    $sectionsList.empty();

    if (!availableSections || Object.keys(availableSections).length === 0) {
      $sectionsList.html(
        '<div class="sb-empty">No hay secciones Twig disponibles</div>'
      );
      return;
    }

    Object.entries(availableSections).forEach(([id, section]) => {
      const name = section.name || id;
      const hasSchema = section.has_schema;
      const schemaIndicator = hasSchema ? "⚙️" : "📄";
      const templateFile = section.template_file || id + ".twig";

      const $item = $(`
              <li class="sb-list-item" data-id="${id}" title="${
        section.description || "Sección " + name
      }">
                  <div class="sb-section-info">
                      <div class="sb-section-name">
                          <span class="sb-section-indicator">${schemaIndicator}</span>
                          ${name}
                      </div>
                      <div class="sb-section-meta">
                          <span class="sb-template-path">${templateFile}</span>
                          <span class="sb-section-schema-info">
                              ${hasSchema ? "con esquema" : "sin esquema"}
                          </span>
                      </div>
                  </div>
              </li>
          `);

      $sectionsList.append($item);
    });
  }

  // Renderizar editor de plantilla
  function renderTemplateEditor() {
    $editor.empty();

    if (!selectedTemplate) {
      $editor.html(
        '<div class="sb-empty">Selecciona una plantilla para editar o crea una nueva</div>'
      );
      return;
    }

    const propertiesPanel = `
          <div class="sb-panel">
              <h3 class="sb-panel-title">Propiedades de la Plantilla</h3>
              <div class="sb-panel-content">
                  <div class="sb-form-group">
                      <label class="sb-form-label">Nombre</label>
                      <input class="sb-form-input sb-template-input" type="text" 
                             value="${selectedTemplate.name || ""}"
                             data-field="name">
                  </div>
                  
                  <div class="sb-form-group">
                      <label class="sb-form-label">Descripción</label>
                      <textarea class="sb-form-textarea sb-template-input"
                                data-field="description">${
                                  selectedTemplate.description || ""
                                }</textarea>
                  </div>

                  <div class="sb-timber-info">
                      <h4>Información del Template Timber/Twig:</h4>
                      <ul>
                          <li><strong>Order:</strong> ${
                            selectedTemplate.order
                              ? selectedTemplate.order.length
                              : 0
                          } secciones ordenadas</li>
                          <li><strong>Sections:</strong> ${
                            Object.keys(selectedTemplate.sections || {}).length
                          } secciones configuradas</li>
                          <li><strong>Renderizado:</strong> <code>sections[item].section_id</code> → <code>{item}.twig</code></li>
                      </ul>
                  </div>
              </div>
          </div>
      `;

    const sectionsPanel = `
          <div class="sb-panel">
              <h3 class="sb-panel-title">Secciones del Template</h3>
              <div class="sb-panel-content">
                  ${renderSections()}
              </div>
          </div>
      `;

    $editor.html(propertiesPanel + sectionsPanel);
  }

  // Renderizar secciones
  function renderSections() {
    if (!selectedTemplate.order || selectedTemplate.order.length === 0) {
      return '<div class="sb-empty">No hay secciones en esta plantilla. Añade secciones desde la barra lateral.</div>';
    }

    let html =
      '<div class="sb-sections-order-info"><small>El orden de las secciones determina cómo aparecerán en el template Twig usando <code>{% for item in order %}</code></small></div>';

    selectedTemplate.order.forEach((sectionKey) => {
      const section = selectedTemplate.sections[sectionKey];
      if (!section) return;

      const isExpanded = expandedSections[sectionKey] || false;
      const sectionType = section.section_id;
      const sectionInfo = availableSections[sectionType] || {
        name: sectionType,
      };
      const hasSchema = sectionSchemas[sectionType] ? true : false;
      const schemaIndicator = hasSchema ? "⚙️" : "📄";

      html += `
              <div class="sb-section" data-id="${sectionKey}">
                  <div class="sb-section-header">
                      <h3 class="sb-section-title">
                          <span class="sb-section-indicator">${schemaIndicator}</span>
                          ${sectionInfo.name || sectionType}
                          <small class="sb-template-path">(${sectionType}.twig)</small>
                      </h3>
                      <div class="sb-section-actions">
                          <button class="sb-button sb-button-secondary sb-button-sm sb-move-up" title="Subir">↑</button>
                          <button class="sb-button sb-button-secondary sb-button-sm sb-move-down" title="Bajar">↓</button>
                          <button class="sb-button sb-button-danger sb-button-sm sb-remove-section" title="Eliminar">×</button>
                      </div>
                  </div>
                  
                  ${
                    isExpanded ? renderSectionSettings(sectionKey, section) : ""
                  }
              </div>
          `;
    });

    return html;
  }

  // Renderizar configuración de sección
  function renderSectionSettings(sectionKey, section) {
    const sectionType = section.section_id;
    const schema = sectionSchemas[sectionType]?.schema || {};
    const sectionProperties = schema.properties || {};
    const blocksDefinition = schema.blocks || {};

    let html = `
        <div class="sb-section-content">
            <div class="sb-form-group">
                <label class="sb-form-label">Template Twig</label>
                <div class="sb-template-reference">
                    <code>views/sections/${sectionType}.twig</code>
                </div>
                <p class="sb-form-help">Template que se renderizará usando <code>{% include "sections/" ~ sections[item].section_id ~ ".twig" %}</code></p>
            </div>
    `;

    // Renderizar configuraciones de la sección si existen
    if (Object.keys(sectionProperties).length > 0) {
      html += "<h4>Configuración de la Sección:</h4>";
      html += '<div class="sb-settings-group">';

      Object.entries(sectionProperties).forEach(([key, property]) => {
        const value =
          section.settings && section.settings[key] !== undefined
            ? section.settings[key]
            : property.default || "";
        html += renderSchemaField(sectionKey, key, property, value, "section");
      });

      html += "</div>";
      html +=
        '<p class="sb-form-help"><strong>Acceso en Twig:</strong> <code>{{ section.settings.campo }}</code></p>';
    }

    // Renderizar bloques si existen definiciones
    if (Object.keys(blocksDefinition).length > 0) {
      html += '<div class="sb-blocks-section">';
      html += "<h4>Bloques Disponibles:</h4>";

      // Mostrar bloques definidos en el schema
      html += '<div class="sb-available-blocks">';
      Object.entries(blocksDefinition).forEach(([blockId, blockDef]) => {
        html += `
                <div class="sb-available-block">
                    <button class="sb-button sb-button-secondary sb-add-block" 
                            data-section="${sectionKey}" 
                            data-block-type="${blockId}"
                            title="${
                              blockDef.description ||
                              "Agregar bloque " + blockDef.name
                            }">
                        + ${blockDef.name}
                    </button>
                </div>
            `;
      });
      html += "</div>";

      // Renderizar bloques existentes en la sección
      html += '<div class="sb-section-blocks">';
      if (
        section.blocks &&
        Array.isArray(section.blocks) &&
        section.blocks.length > 0
      ) {
        html += "<h5>Bloques en esta sección:</h5>";

        section.blocks.forEach((block, blockIndex) => {
          const blockType = block.type || block.block_id || "default";
          const blockDefinition = blocksDefinition[blockType] || {
            name: "Bloque sin definir",
            properties: {},
          };

          html += renderBlockSettings(
            sectionKey,
            blockIndex,
            block,
            blockDefinition,
            blocksDefinition
          );
        });
      } else {
        html +=
          '<p class="sb-empty-blocks">No hay bloques añadidos. Usa los botones de arriba para agregar bloques.</p>';
      }
      html += "</div>";

      html +=
        '<p class="sb-form-help"><strong>Acceso en Twig:</strong> <code>{% for block in section.blocks %} ... {% endfor %}</code></p>';
      html += "</div>";
    }

    // Si no hay esquema
    if (
      Object.keys(sectionProperties).length === 0 &&
      Object.keys(blocksDefinition).length === 0
    ) {
      html += `
            <div class="sb-no-schema">
                <p>Esta sección no tiene un esquema definido en <code>schemas/${sectionType}.php</code></p>
                <p>Las variables estarán disponibles en el template Twig como:</p>
                <ul>
                    <li><code>{{ section.settings.variable_name }}</code> para configuraciones</li>
                    <li><code>{{ section.blocks }}</code> para bloques</li>
                </ul>
                <p>Puedes crear un archivo de esquema para generar campos de configuración y definir bloques automáticamente.</p>
            </div>
        `;
    }

    html += "</div>";
    return html;
  }

  // Funciones para manejar bloques
  function addBlock(sectionKey, blockType) {
    if (
      !selectedTemplate ||
      !selectedTemplate.sections ||
      !selectedTemplate.sections[sectionKey]
    ) {
      return;
    }

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks) {
      section.blocks = [];
    }

    // Crear nuevo bloque
    const newBlock = {
      type: blockType,
      settings: {},
    };

    section.blocks.push(newBlock);
    renderTemplateEditor();

    const blockDefinition =
      sectionSchemas[section.section_id]?.schema?.blocks?.[blockType];
    const blockName = blockDefinition?.name || blockType;
    showMessage("success", `Bloque "${blockName}" añadido`);
  }

  function removeBlock(sectionKey, blockIndex) {
    if (
      !selectedTemplate ||
      !selectedTemplate.sections ||
      !selectedTemplate.sections[sectionKey]
    ) {
      return;
    }

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks || !section.blocks[blockIndex]) {
      return;
    }

    section.blocks.splice(blockIndex, 1);
    renderTemplateEditor();
    showMessage("success", "Bloque eliminado");
  }

  function moveBlockUp(sectionKey, blockIndex) {
    if (!selectedTemplate || blockIndex <= 0) return;

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks || !section.blocks[blockIndex]) return;

    [section.blocks[blockIndex], section.blocks[blockIndex - 1]] = [
      section.blocks[blockIndex - 1],
      section.blocks[blockIndex],
    ];

    renderTemplateEditor();
  }

  function moveBlockDown(sectionKey, blockIndex) {
    if (!selectedTemplate) return;

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks || blockIndex >= section.blocks.length - 1) return;

    [section.blocks[blockIndex], section.blocks[blockIndex + 1]] = [
      section.blocks[blockIndex + 1],
      section.blocks[blockIndex],
    ];

    renderTemplateEditor();
  }

  function changeBlockType(sectionKey, blockIndex, newBlockType) {
    if (!selectedTemplate) return;

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks || !section.blocks[blockIndex]) return;

    // Mantener configuraciones compatibles al cambiar tipo
    const oldBlock = section.blocks[blockIndex];
    section.blocks[blockIndex] = {
      type: newBlockType,
      settings: oldBlock.settings || {},
    };

    renderTemplateEditor();
    showMessage("success", `Tipo de bloque cambiado a "${newBlockType}"`);
  }

  function updateBlockSetting(sectionKey, blockIndex, key, value) {
    if (
      !selectedTemplate ||
      !selectedTemplate.sections ||
      !selectedTemplate.sections[sectionKey]
    ) {
      return;
    }

    const section = selectedTemplate.sections[sectionKey];
    if (!section.blocks || !section.blocks[blockIndex]) {
      return;
    }

    if (!section.blocks[blockIndex].settings) {
      section.blocks[blockIndex].settings = {};
    }

    section.blocks[blockIndex].settings[key] = value;
    console.log(
      `Configuración de bloque actualizada: ${sectionKey}[${blockIndex}].settings.${key} = `,
      value
    );
  }

  // Eventos adicionales para manejar bloques (agregar después de registerEvents)
  function registerBlockEvents() {
    // Agregar bloque
    $editor.on("click", ".sb-add-block", function (e) {
      e.preventDefault();
      const sectionKey = $(this).data("section");
      const blockType = $(this).data("block-type");
      addBlock(sectionKey, blockType);
    });

    // Eliminar bloque
    $editor.on("click", ".sb-remove-block", function (e) {
      e.stopPropagation();
      const sectionKey = $(this).closest(".sb-block").data("section");
      const blockIndex = $(this).closest(".sb-block").data("block-index");
      removeBlock(sectionKey, blockIndex);
    });

    // Mover bloque arriba/abajo
    $editor.on("click", ".sb-move-block-up", function (e) {
      e.stopPropagation();
      const sectionKey = $(this).closest(".sb-block").data("section");
      const blockIndex = $(this).closest(".sb-block").data("block-index");
      moveBlockUp(sectionKey, blockIndex);
    });

    $editor.on("click", ".sb-move-block-down", function (e) {
      e.stopPropagation();
      const sectionKey = $(this).closest(".sb-block").data("section");
      const blockIndex = $(this).closest(".sb-block").data("block-index");
      moveBlockDown(sectionKey, blockIndex);
    });

    // Cambiar tipo de bloque
    $editor.on("change", ".sb-block-type-selector", function () {
      const sectionKey = $(this).data("section");
      const blockIndex = $(this).data("block-index");
      const newBlockType = $(this).val();
      changeBlockType(sectionKey, blockIndex, newBlockType);
    });

    // Actualizar configuración de bloque
    $editor.on("change", ".sb-block-setting-input", function () {
      const $this = $(this);
      const sectionKey = $this.closest(".sb-section").data("id");
      const blockIndex = $this.data("block-index");
      const setting = $this.data("setting");
      let value = $this.val();

      // Convertir según el tipo de dato
      const type = $this.data("type");
      if (type === "number") {
        value = parseFloat(value);
      } else if (type === "boolean") {
        value = $this.is(":checked");
      }

      updateBlockSetting(sectionKey, blockIndex, setting, value);
    });
  }

  // Renderizar campo de esquema
  function renderSchemaField(
    sectionKey,
    fieldKey,
    property,
    value,
    context = "section",
    blockIndex = null
  ) {
    const type = property.type || "string";
    const title = property.title || fieldKey;
    const description = property.description || "";

    // Generar IDs únicos
    const fieldId =
      blockIndex !== null
        ? `field-${sectionKey}-${blockIndex}-${fieldKey}`
        : `field-${sectionKey}-${fieldKey}`;
    const previewId = `preview-${fieldId}`;

    // Clases CSS para el input
    const inputClasses =
      context === "block"
        ? "sb-form-input sb-block-setting-input"
        : "sb-form-input sb-setting-input";

    let fieldHtml = "";

    if (type === "string" && property.format === "image") {
      // Campo de imagen
      const hasImage = value !== "";

      fieldHtml = `
            <div class="sb-form-group">
                <label class="sb-form-label">${title}</label>
                <div class="sb-image-field">
                    <input type="text" 
                        id="${fieldId}"
                        class="${inputClasses}" 
                        value="${value}"
                        data-setting="${fieldKey}"
                        data-type="string"
                        data-context="${context}"
                        ${
                          blockIndex !== null
                            ? `data-block-index="${blockIndex}"`
                            : ""
                        }
                    />
                    <button class="sb-button sb-button-secondary sb-select-image" 
                            data-input="${fieldId}" 
                            data-preview="${previewId}">
                        Seleccionar
                    </button>
                </div>
                <div id="${previewId}" class="sb-image-preview" style="${
        hasImage ? "" : "display: none;"
      }">
                    ${
                      hasImage
                        ? `<img src="${value}" alt="Vista previa" />`
                        : ""
                    }
                    <button class="sb-button sb-button-danger sb-remove-image" 
                            data-input="${fieldId}" 
                            data-preview="${previewId}">×</button>
                </div>
                <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                ${
                  description
                    ? `<p class="sb-form-help">${description}</p>`
                    : ""
                }
            </div>
        `;
    } else if (property.enum) {
      // Select con opciones
      fieldHtml = `
            <div class="sb-form-group">
                <label class="sb-form-label">${title}</label>
                <select class="sb-form-select ${inputClasses.replace(
                  "sb-form-input",
                  "sb-form-select"
                )}" 
                        data-setting="${fieldKey}" 
                        data-type="string"
                        data-context="${context}"
                        ${
                          blockIndex !== null
                            ? `data-block-index="${blockIndex}"`
                            : ""
                        }>
                    ${property.enum
                      .map((option, idx) => {
                        const label = property.enumNames
                          ? property.enumNames[idx]
                          : option;
                        return `<option value="${option}" ${
                          value === option ? "selected" : ""
                        }>${label}</option>`;
                      })
                      .join("")}
                </select>
                <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                ${
                  description
                    ? `<p class="sb-form-help">${description}</p>`
                    : ""
                }
            </div>
        `;
    } else if (type === "boolean") {
      // Checkbox
      fieldHtml = `
            <div class="sb-form-group">
                <label class="sb-form-label">
                    <input type="checkbox" 
                        class="${inputClasses}"
                        ${value ? "checked" : ""}
                        data-setting="${fieldKey}"
                        data-type="boolean"
                        data-context="${context}"
                        ${
                          blockIndex !== null
                            ? `data-block-index="${blockIndex}"`
                            : ""
                        }
                    />
                    ${title}
                </label>
                <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                ${
                  description
                    ? `<p class="sb-form-help">${description}</p>`
                    : ""
                }
            </div>
        `;
    } else if (type === "number") {
      // Input numérico
      const min =
        property.minimum !== undefined ? `min="${property.minimum}"` : "";
      const max =
        property.maximum !== undefined ? `max="${property.maximum}"` : "";
      const step =
        property.multipleOf !== undefined
          ? `step="${property.multipleOf}"`
          : "";

      fieldHtml = `
            <div class="sb-form-group">
                <label class="sb-form-label">${title}</label>
                <input class="${inputClasses}" type="number" 
                    value="${value}"
                    data-setting="${fieldKey}"
                    data-type="number"
                    data-context="${context}"
                    ${
                      blockIndex !== null
                        ? `data-block-index="${blockIndex}"`
                        : ""
                    }
                    ${min} ${max} ${step}
                />
                <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                ${
                  description
                    ? `<p class="sb-form-help">${description}</p>`
                    : ""
                }
            </div>
        `;
    } else if (type === "color") {
      // Selector de color
      fieldHtml = `
            <div class="sb-form-group">
                <label class="sb-form-label">${title}</label>
                <input class="${inputClasses}" type="color" 
                    value="${value}"
                    data-setting="${fieldKey}"
                    data-type="string"
                    data-context="${context}"
                    ${
                      blockIndex !== null
                        ? `data-block-index="${blockIndex}"`
                        : ""
                    }
                />
                <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                ${
                  description
                    ? `<p class="sb-form-help">${description}</p>`
                    : ""
                }
            </div>
        `;
    } else {
      // Input de texto por defecto
      const inputType = property.format === "textarea" ? "textarea" : "text";
      const placeholder = property.placeholder
        ? `placeholder="${property.placeholder}"`
        : "";

      if (inputType === "textarea") {
        fieldHtml = `
                <div class="sb-form-group">
                    <label class="sb-form-label">${title}</label>
                    <textarea class="sb-form-textarea ${inputClasses.replace(
                      "sb-form-input",
                      "sb-form-textarea"
                    )}" 
                        data-setting="${fieldKey}"
                        data-type="string"
                        data-context="${context}"
                        ${
                          blockIndex !== null
                            ? `data-block-index="${blockIndex}"`
                            : ""
                        }
                        ${placeholder}>${value}</textarea>
                    <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                    ${
                      description
                        ? `<p class="sb-form-help">${description}</p>`
                        : ""
                    }
                </div>
            `;
      } else {
        fieldHtml = `
                <div class="sb-form-group">
                    <label class="sb-form-label">${title}</label>
                    <input class="${inputClasses}" type="${inputType}" 
                        value="${value}"
                        data-setting="${fieldKey}"
                        data-type="string"
                        data-context="${context}"
                        ${
                          blockIndex !== null
                            ? `data-block-index="${blockIndex}"`
                            : ""
                        }
                        ${placeholder}
                    />
                    <small class="sb-form-help">Disponible en Twig como: <code>{{ ${context}.settings.${fieldKey} }}</code></small>
                    ${
                      description
                        ? `<p class="sb-form-help">${description}</p>`
                        : ""
                    }
                </div>
            `;
      }
    }

    return fieldHtml;
  }

  // Funciones auxiliares
  function removeSection(sectionKey) {
    if (!selectedTemplate) return;

    delete selectedTemplate.sections[sectionKey];
    selectedTemplate.order = selectedTemplate.order.filter(
      (id) => id !== sectionKey
    );
    renderTemplateEditor();
    showMessage("success", "Sección eliminada");
  }

  function moveSectionUp(sectionKey) {
    if (!selectedTemplate) return;

    const currentIndex = selectedTemplate.order.indexOf(sectionKey);
    if (currentIndex <= 0) return;

    const newOrder = [...selectedTemplate.order];
    [newOrder[currentIndex], newOrder[currentIndex - 1]] = [
      newOrder[currentIndex - 1],
      newOrder[currentIndex],
    ];
    selectedTemplate.order = newOrder;

    renderTemplateEditor();
  }

  function moveSectionDown(sectionKey) {
    if (!selectedTemplate) return;

    const currentIndex = selectedTemplate.order.indexOf(sectionKey);
    if (
      currentIndex === -1 ||
      currentIndex >= selectedTemplate.order.length - 1
    )
      return;

    const newOrder = [...selectedTemplate.order];
    [newOrder[currentIndex], newOrder[currentIndex + 1]] = [
      newOrder[currentIndex + 1],
      newOrder[currentIndex],
    ];
    selectedTemplate.order = newOrder;

    renderTemplateEditor();
  }

  function toggleSection(sectionKey) {
    expandedSections[sectionKey] = !expandedSections[sectionKey];
    renderTemplateEditor();
  }

  // Actualizar la función updateSectionSetting para manejar el contexto de settings
  function updateSectionSetting(sectionKey, key, value) {
    if (
      !selectedTemplate ||
      !selectedTemplate.sections ||
      !selectedTemplate.sections[sectionKey]
    ) {
      return;
    }

    const section = selectedTemplate.sections[sectionKey];

    // Para mantener compatibilidad con Twig, las configuraciones van en section.settings
    if (!section.settings) {
      section.settings = {};
    }

    section.settings[key] = value;
    console.log(
      `Configuración de sección actualizada: ${sectionKey}.settings.${key} = `,
      value
    );
  }

  function updateTemplateField(field, value) {
    if (!selectedTemplate) return;

    selectedTemplate[field] = value;

    if (field === "name") {
      $templateTitle.text(value || "Sin título");
    }
  }

  function updateActiveTemplate() {
    $templateTitle.text(
      selectedTemplate
        ? selectedTemplate.name || "Sin título"
        : "Template Builder - Timber/Twig"
    );

    if (selectedTemplate) {
      $saveButton.show();
    } else {
      $saveButton.hide();
    }

    $templatesList.find("li").removeClass("active");
    if (selectedTemplate && selectedTemplate._originalId) {
      $templatesList
        .find(`li[data-id="${selectedTemplate._originalId}"]`)
        .addClass("active");
    }
  }

  function initExpandedSections() {
    expandedSections = {};

    if (!selectedTemplate || !selectedTemplate.order) return;

    selectedTemplate.order.forEach((sectionKey) => {
      expandedSections[sectionKey] = false;
    });

    if (selectedTemplate.order.length > 0) {
      expandedSections[selectedTemplate.order[0]] = true;
    }
  }

  function selectWordPressImage($button) {
    if (!window.wp || !window.wp.media) {
      console.error("WordPress Media Library no está disponible");
      return;
    }

    const inputId = $button.data("input");
    const previewId = $button.data("preview");

    const frame = wp.media({
      title: "Seleccionar imagen",
      button: { text: "Usar imagen" },
      multiple: false,
    });

    frame.on("select", function () {
      const attachment = frame.state().get("selection").first().toJSON();

      $(`#${inputId}`).val(attachment.url).trigger("change");

      const $preview = $(`#${previewId}`);
      $preview.find("img").remove();
      $preview.prepend(`<img src="${attachment.url}" alt="Vista previa" />`);
      $preview.show();
    });

    frame.open();
  }

  function removeSelectedImage($button) {
    const inputId = $button.data("input");
    const previewId = $button.data("preview");

    $(`#${inputId}`).val("").trigger("change");
    $(`#${previewId}`).hide().find("img").remove();
  }

  function showMessage(type, text) {
    $message
      .removeClass(
        "sb-message-success sb-message-error sb-message-warning sb-message-info"
      )
      .addClass(`sb-message-${type}`)
      .text(text)
      .show();

    setTimeout(() => {
      $message.hide();
    }, 5000);
  }

  function setLoading(isLoading) {
    if (isLoading) {
      $loading.show();
      $builder.hide();
    } else {
      $loading.hide();
      $builder.show();
    }
  }

  // Inicializar cuando el documento esté listo
  $(document).ready(function () {
    init();
  });
})(jQuery);
