class RaffleController {
  constructor(raffleModel) {
    this.raffleModel = raffleModel;
  }

  /**
   * Controller para LISTA de rifas
   */
  listData() {
    const raffleModel = this.raffleModel;

    return {
      raffles: [],
      loading: false,
      search: "",
      statusFilter: "",

      async init() {
        console.log("🎟️ RaffleList inicializado");
        await this.loadRaffles();

        window.addEventListener("route-changed", (e) => {
          if (e.detail.view === "raffle-list") {
            console.log("🔄 Volviendo a lista de rifas - Recargando datos");
            this.loadRaffles();
          }
        });
      },

      async loadRaffles() {
        this.loading = true;
        try {
          this.raffles = await raffleModel.getAll();
          console.log("✅ Rifas cargadas:", this.raffles.length);
        } catch (error) {
          console.error("❌ Error cargando rifas:", error);
          this.raffles = [];
        }
        this.loading = false;
      },

      editRaffle(raffleId) {
        window.RaffleAppAdmin.router.navigate(`/raffle/edit/${raffleId}`);
      },

      createNew() {
        window.RaffleAppAdmin.router.navigate("/raffle/new");
      },

      // ✅ Nuevo método para obtener URL pública
      getRaffleUrl(raffleId) {
        // Obtener el post para conseguir el slug
        const raffle = this.raffles.find((r) => r.id === raffleId);
        if (raffle && raffle.slug) {
          return `${window.location.origin}/rifa/${raffle.slug}/`;
        }
        // Fallback: usar query param
        return `${window.location.origin}/?p=${raffleId}`;
      },

      async deleteRaffle(raffleId, raffleName) {
        if (!confirm(`¿Eliminar la rifa "${raffleName}"?`)) return;

        this.loading = true;
        try {
          const result = await raffleModel.delete(raffleId);
          if (result.success) {
            alert("Rifa eliminada exitosamente");
            await this.loadRaffles();
          } else {
            alert("Error al eliminar rifa");
          }
        } catch (error) {
          console.error("Error:", error);
          alert("Error al eliminar rifa");
        }
        this.loading = false;
      },

      // Computed
      get filteredRaffles() {
        let filtered = this.raffles;

        if (this.search) {
          const search = this.search.toLowerCase();
          filtered = filtered.filter((r) =>
            r.title.toLowerCase().includes(search)
          );
        }

        if (this.statusFilter) {
          filtered = filtered.filter((r) => r.status === this.statusFilter);
        }

        return filtered;
      },

      getStatusBadge(status) {
        const badges = {
          active: "bg-green-100 text-green-800",
          paused: "bg-yellow-100 text-yellow-800",
          completed: "bg-blue-100 text-blue-800",
          cancelled: "bg-red-100 text-red-800",
        };
        return badges[status] || "bg-gray-100 text-gray-800";
      },

      getStatusText(status) {
        const texts = {
          active: "Activa",
          paused: "Pausada",
          completed: "Completada",
          cancelled: "Cancelada",
        };
        return texts[status] || status;
      },
    };
  }

  /**
   * Controller para FORMULARIO de rifa (crear/editar)
   */
  formData() {
    const raffleModel = this.raffleModel;

    return {
      // Datos del formulario
      raffle: {
        id: null,
        title: "",
        content: "",
        price: 0,
        allow_installments: false,
        ticket_limit: 100,
        gallery: [], // Array de URLs de imágenes
        prizes: [{ title: "", description: "", image: "", detail: "" }],
        status: "active",
      },

      loading: false,
      isEditing: false,

      init() {
        console.log("📝 RaffleForm inicializado");

        // ✅ Intentar cargar inmediatamente
        this.checkAndLoadRaffle();

        // ✅ También escuchar cambios en el router
        window.addEventListener("route-changed", (e) => {
          console.log("🔄 Route changed en formulario:", e.detail);
          if (e.detail.view === "raffle-form" && e.detail.params.id) {
            this.checkAndLoadRaffle();
          }
        });
      },

      checkAndLoadRaffle() {
        const raffleId = window.RaffleAppAdmin.router.getParam("id");
        console.log("🔍 Verificando ID de rifa:", raffleId);

        if (raffleId && raffleId !== this.raffle.id) {
          this.isEditing = true;
          this.loadRaffle(raffleId);
        } else if (!raffleId) {
          console.log("➕ Modo creación (sin ID)");
          this.isEditing = false;
        }
      },

      async loadRaffle(id) {
        this.loading = true;
        try {
          const data = await raffleModel.getById(id);
          if (data) {
            this.raffle = data;
            console.log("✅ Rifa cargada para editar:", data);
          }
        } catch (error) {
          console.error("❌ Error cargando rifa:", error);
        }
        this.loading = false;
      },

      async saveRaffle() {
        // Validación
        if (
          !this.raffle.title ||
          !this.raffle.price ||
          !this.raffle.ticket_limit
        ) {
          alert("Por favor completa todos los campos obligatorios");
          return;
        }

        this.loading = true;

        try {
          let result;

          if (this.isEditing) {
            result = await raffleModel.update(this.raffle.id, this.raffle);
          } else {
            result = await raffleModel.create(this.raffle);
          }

          if (result.success) {
            alert(
              this.isEditing ? "Rifa actualizada" : "Rifa creada exitosamente"
            );
            window.RaffleAppAdmin.router.navigate("/raffles");
          } else {
            alert("Error al guardar rifa");
          }
        } catch (error) {
          console.error("Error:", error);
          alert("Error al guardar rifa");
        }

        this.loading = false;
      },

      // Métodos para premios (repeater)
      addPrize() {
        this.raffle.prizes.push({
          title: "",
          description: "",
          image: "",
          detail: "",
        });
      },

      removePrize(index) {
        if (this.raffle.prizes.length > 1) {
          this.raffle.prizes.splice(index, 1);
        } else {
          alert("Debe haber al menos un premio");
        }
      },

      // Método para abrir Media Library (WordPress)
      openMediaLibrary(type = "single", callback) {
        if (typeof wp === "undefined" || !wp.media) {
          alert("Media Library no disponible");
          return;
        }

        const frame = wp.media({
          title:
            type === "single" ? "Seleccionar imagen" : "Seleccionar imágenes",
          button: { text: "Usar imágenes" },
          multiple: type === "gallery",
        });

        frame.on("select", () => {
          const selection = frame.state().get("selection");

          if (type === "gallery") {
            const urls = [];
            selection.each((attachment) => {
              urls.push(attachment.get("url"));
            });
            callback(urls);
          } else {
            const attachment = selection.first();
            callback(attachment.get("url"));
          }
        });

        frame.open();
      },

      selectGallery() {
        this.openMediaLibrary("gallery", (urls) => {
          this.raffle.gallery = urls;
        });
      },

      selectPrizeImage(index) {
        this.openMediaLibrary("single", (url) => {
          this.raffle.prizes[index].image = url;
        });
      },

      removeGalleryImage(index) {
        this.raffle.gallery.splice(index, 1);
      },

      goBack() {
        window.RaffleAppAdmin.router.navigate("/raffles");
      },
    };
  }
}

export default RaffleController;
