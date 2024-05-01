/* jQuery File Tree. Authors - Cory S.N. LaViska & Dave Rogers. Copyright 2008 A Beautiful Site, LLC. - Adapted by Bergware for use in Unraid */
jQuery && function($) {
	$.extend($.fn, {
		fileTree: function(options, callback, onCancel) {
			void 0 === options.root && (options.root = "/mnt/"),
				void 0 === options.top && (options.top = "/mnt/"),
				void 0 === options.filter && (options.filter = ""),
				void 0 === options.match && (options.match = ".*"),
				void 0 === options.script && (options.script = "/webGui/include/FileTree.php"),
				void 0 === options.folderEvent && (options.folderEvent = "click"),
				void 0 === options.expandSpeed && (options.expandSpeed = 300),
				void 0 === options.collapseSpeed && (options.collapseSpeed = 300),
				void 0 === options.expandEasing && (options.expandEasing = null),
				void 0 === options.collapseEasing && (options.collapseEasing = null),
				void 0 === options.multiFolder && (options.multiFolder = !1),
				void 0 === options.loadMessage && (options.loadMessage = "Loading..."),
				void 0 === options.multiSelect && (options.multiSelect = !1),
				void 0 === options.allowBrowsing && (options.allowBrowsing = !1),
				void 0 === options.pickexclude && (options.pickexclude = "");
			$(this).each(function() {
				function showTree($this, dir, showParent) {
					$this.addClass("wait"),
						$(".jqueryFileTree.start").remove(),
						/* Modify the post data to include pickexclude. */
						$.post(options.script, {
							dir: dir,
							root: options.top,
							multiSelect: options.multiSelect,
							filter: options.filter,
							match: options.match,
							show_parent: showParent,
							/* Add pickexclude parameter. */
							pickexclude: options.pickexclude
						}).done(function(data) {
							var $parent;
							$this.find(".start").html(""),
								$this.removeClass("wait").append(data),
								options.root == dir ? $this.find("UL:hidden").show() : $this.find("UL:hidden").slideDown({
									duration: options.expandSpeed,
									easing: options.expandEasing
								}),
								$($parent = $this).find("LI A").on(options.folderEvent, function(event) {
									event.preventDefault();
									var node = {};
									return node.li = $(this).closest("li"),
										node.type = node.li.hasClass("directory") ? "directory" : "file",
										node.value = $(this).text(),
										node.rel = $(this).prop("rel"),
										".." == $(this).text() ? (options.root = node.rel, callback && callback($(this).attr("rel")), triggerEvent($(this), "filetreefolderclicked", node), root = $(this).closest("ul.jqueryFileTree"), root.html('<ul class="jqueryFileTree start"><li class="wait">' + options.loadMessage + "<li></ul>"), showTree($(root), options.root, options.allowBrowsing)) : $(this).parent().hasClass("directory") ? ($(this).parent().hasClass("collapsed") ? (triggerEvent($(this), "filetreeexpand", node), options.multiFolder || ($(this).parent().parent().find("UL").slideUp({
												duration: options.collapseSpeed,
												easing: options.collapseEasing
											}), $(this).parent().parent().find("LI.directory").removeClass("expanded").addClass("collapsed")), $(this).parent().removeClass("collapsed").addClass("expanded"), $(this).parent().find("UL").remove(), showTree($(this).parent(), $(this).attr("rel").match(/.*\//)[0], !1)) : (triggerEvent($(this), "filetreecollapse", node), $(this).parent().find("UL").slideUp({
												duration: options.collapseSpeed,
												easing: options.collapseEasing
											}), $(this).parent().removeClass("expanded").addClass("collapsed"), triggerEvent($(this), "filetreecollapsed", node)), callback && callback($(this).attr("rel")), triggerEvent($(this), "filetreefolderclicked", node)) : (onCancel && onCancel($(this).attr("rel")), triggerEvent($(this), "filetreeclicked", node)), !1
								}),
								"click" != options.folderEvent.toLowerCase && $parent.find("LI A").on("click", function(event) {
									return event.preventDefault(), !1
								}),
								triggerEvent($(this), "filetreeexpanded", data)
						}).fail(function() {
							$this.find(".start").html(""),
								$this.removeClass("wait").append("<li>Unable to get file tree information</li>")
						})
				}

				function triggerEvent($elem, event, data) {
					data.trigger = event,
						$elem.trigger(event, data)
				}
				$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + options.loadMessage + "<li></ul>"),
					showTree($(this), options.root, options.allowBrowsing),
					$(this).on("change", "input:checkbox", function() {
						var node = {};
						node.li = $(this).closest("li"),
							node.type = node.li.hasClass("directory") ? "directory" : "file",
							node.value = node.li.children("a").text(),
							node.rel = node.li.children("a").prop("rel"),
							node.li.find("input:checkbox").prop("checked", $(this).prop("checked")),
							$(this).prop("checked") ? triggerEvent($(this), "filetreechecked", node) : triggerEvent($(this), "filetreeunchecked", node)
					})
			})
		},
		fileTreeAttach: function(options, callback, onCancel) {
			var settings = {};
			$.isFunction(options) ? ($.isFunction(callback) && (onCancel = callback), callback = options) : options && $.extend(settings, options),
				$(this).each(function() {
					var $this = $(this),
						config = $.extend({}, settings, $this.data()),
						$fileTree = $this.next(".fileTree");
					0 === $fileTree.length && ($(document).mousedown(function(event) {
							var $fileTree = $(".fileTree");
							$fileTree.is(event.target) || 0 !== $fileTree.has(event.target).length || $fileTree.slideUp("fast")
						}),
						$fileTree = $("<div>", {
							class: "textarea fileTree"
						}),
						$this.after($fileTree)),
						$this.click(function() {
							$fileTree.is(":visible") ? $fileTree.slideUp("fast") : ("" === $fileTree.html() && ($fileTree.html('<span style="padding-left: 20px"><img src="/webGui/images/spinner.gif"> Loading...</span>'),
									$fileTree.fileTree({
										root: config.pickroot,
										top: config.picktop,
										filter: (config.pickfilter || "").split(","),
										match: config.pickmatch || ".*",
										/* Include pickexclude parameter in fileTreeAttach. */
										pickexclude: config.pickexclude
									}, $.isFunction(callback) ? callback : function(data) {
										$this.val(data).change(),
											config.hasOwnProperty("pickcloseonfile") && $fileTree.slideUp("fast")
									}, $.isFunction(onCancel) ? onCancel : function(data) {
										config.hasOwnProperty("pickfolders") && $this.val(data).change()
									})),
								$fileTree.offset({
									left: $this.position().left
								}),
								$fileTree.slideDown("fast"))
						})
				})
		}
	})
}(jQuery);
