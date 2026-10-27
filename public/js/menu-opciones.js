/**
 * Lógica del selector de presentación y opciones (salsas, papas, Arma tus Papas...).
 * La usan la pantalla del mesero y la del cliente; cada una pone su propio diseño.
 * El precio que se calcula aquí es solo para mostrarlo: el servidor lo vuelve a calcular.
 *
 * Una selección es { variantId, cantidades: { [optionId]: n } }.
 */
(function () {
    const grupoDe = (producto, optionId) =>
        (producto.option_groups || []).find(g => g.options.some(o => o.id === optionId));

    const totalGrupo = (grupo, sel) =>
        grupo.options.reduce((suma, o) => suma + (sel.cantidades[o.id] || 0), 0);

    window.MenuOpciones = {
        necesitaElegir(producto) {
            return (producto.variants || []).length > 0
                || (producto.option_groups || []).length > 0
                || (producto.ingredientes || []).length > 0;
        },

        crear(producto) {
            const variantes = producto.variants || [];
            return { variantId: variantes.length ? variantes[0].id : null, cantidades: {} };
        },

        cantidad(sel, option) {
            return sel.cantidades[option.id] || 0;
        },

        totalGrupo,

        /** Toca una opción: en grupos de una sola elección funciona como radio, si no como casilla. */
        alternar(producto, grupo, option, sel) {
            const actual = sel.cantidades[option.id] || 0;
            if (grupo.max === 1) {
                const nuevo = {};
                Object.keys(sel.cantidades).forEach(id => {
                    if (!grupo.options.some(o => o.id === Number(id))) nuevo[id] = sel.cantidades[id];
                });
                if (!actual) nuevo[option.id] = 1;
                sel.cantidades = nuevo;
                return;
            }
            if (actual) {
                this.quitar(option, sel);
            } else {
                this.sumar(grupo, option, sel);
            }
        },

        puedeSumar(grupo, option, sel) {
            const actual = sel.cantidades[option.id] || 0;
            const tope = grupo.max > 0 ? grupo.max : Infinity;
            return actual < grupo.max_per_option && totalGrupo(grupo, sel) < tope;
        },

        sumar(grupo, option, sel) {
            if (!this.puedeSumar(grupo, option, sel)) return;
            sel.cantidades = { ...sel.cantidades, [option.id]: (sel.cantidades[option.id] || 0) + 1 };
        },

        quitar(option, sel) {
            const nuevo = { ...sel.cantidades };
            if ((nuevo[option.id] || 0) <= 1) {
                delete nuevo[option.id];
            } else {
                nuevo[option.id]--;
            }
            sel.cantidades = nuevo;
        },

        precio(producto, sel) {
            const variante = (producto.variants || []).find(v => v.id === sel.variantId);
            let total = variante ? Number(variante.price) : Number(producto.price);
            Object.entries(sel.cantidades).forEach(([id, n]) => {
                const grupo = grupoDe(producto, Number(id));
                const option = grupo && grupo.options.find(o => o.id === Number(id));
                if (option) total += Number(option.price) * n;
            });
            return Math.round(total * 100) / 100;
        },

        /** Lo obligatorio que falta (bloquea agregar). Hoy solo el sabor de la malteada. */
        pendientes(producto, sel) {
            return (producto.option_groups || [])
                .filter(g => totalGrupo(g, sel) < g.min)
                .map(g => g.min === 1 ? `Elige una opción en «${g.label}»` : `Elige ${g.min} en «${g.label}»`);
        },

        /** Grupo sin costo (salsa, tipo de papa, sazonador): viene incluido con el platillo. */
        esIncluido(grupo) {
            return grupo.options.every(o => Number(o.price) === 0);
        },

        /**
         * Lo incluido que no se eligió. Solo se recuerda, no bloquea: el cliente puede no
         * querer salsa. Los extras con costo nunca se recuerdan.
         */
        recordatorios(producto, sel) {
            return (producto.option_groups || [])
                .filter(g => g.min === 0 && this.esIncluido(g) && totalGrupo(g, sel) === 0)
                .map(g => g.name.toLowerCase());
        },

        /** Texto corto de la regla del grupo: "Obligatorio", "Incluye hasta 2", "Opcional". */
        regla(grupo) {
            if (grupo.min > 0) return grupo.min === grupo.max ? 'Obligatorio' : `Obligatorio · ${grupo.min} a ${grupo.max}`;
            if (this.esIncluido(grupo)) return grupo.max > 1 ? `Incluye hasta ${grupo.max}` : 'Incluido';
            return grupo.max > 1 ? `Opcional · hasta ${grupo.max}` : 'Opcional';
        },

        nombre(producto, sel) {
            const variante = (producto.variants || []).find(v => v.id === sel.variantId);
            return variante ? `${producto.name} (${variante.name})` : producto.name;
        },

        resumen(producto, sel) {
            return (producto.option_groups || [])
                .map(g => {
                    const elegidas = g.options
                        .filter(o => sel.cantidades[o.id])
                        .map(o => (sel.cantidades[o.id] > 1 ? sel.cantidades[o.id] + 'x ' : '') + o.name);
                    return elegidas.length ? `${g.name}: ${elegidas.join(', ')}` : null;
                })
                .filter(Boolean)
                .join(' · ');
        },

        payload(sel) {
            return {
                variant_id: sel.variantId,
                options: Object.entries(sel.cantidades).map(([id, n]) => ({ id: Number(id), quantity: n })),
            };
        },

        /** Identifica la combinación para juntar líneas iguales. */
        clave(producto, sel, excluidos, notas) {
            const opciones = Object.entries(sel.cantidades).map(([id, n]) => id + 'x' + n).sort().join('-');
            return [producto.id, sel.variantId || '', opciones, (excluidos || []).slice().sort().join(','), notas || ''].join('|');
        },

        formatoPrecio(n) {
            return '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },

        /** "Vaso $80 · Litro $160" o "$155" */
        precioCarta(producto) {
            const variantes = producto.variants || [];
            if (!variantes.length) return this.formatoPrecio(producto.price);
            return variantes.map(v => `${v.name} ${this.formatoPrecio(v.price)}`).join(' · ');
        },
    };
})();
