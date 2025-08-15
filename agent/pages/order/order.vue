<template>
	<view class="container">
		<view class="filter-container">
			<uv-drop-down 
				sign="orderFilter" 
				:default-value="[0, 'all', 'search']"
				text-color="#333"
			text-size="30rpx"
			text-active-color="#ff6b35"
			text-active-size="30rpx"
			:extra-icon="{name: 'arrow-down', size: '28rpx', color: '#333'}"
			:extra-active-icon="{name: 'arrow-up', size: '28rpx', color: '#ff6b35'}"
			custom-style="line-height:55rpx;background-color: #ffffff;border-bottom:1px solid #e0e0e0"
				@click="onSelectMenu">
				
				<!-- 订单状态筛选 -->
				<uv-drop-down-item 
					name="status" 
					type="2" 
					:label="statusFilter.label" 
					:value="statusFilter.value">
				</uv-drop-down-item>
				
				<!-- 彩种筛选 -->
				<uv-drop-down-item 
					name="lottery" 
					type="2" 
					:label="lotteryFilter.label" 
					:value="lotteryFilter.value">
				</uv-drop-down-item>
				
				<!-- 搜索按钮 -->
				<uv-drop-down-item 
					name="search" 
					type="1" 
					label="搜索订单" 
					@click="handleSearch">
				</uv-drop-down-item>
				
			</uv-drop-down>
			
			<!-- 筛选弹窗 -->
			<uv-drop-down-popup 
				sign="orderFilter" 
				:current-drop-item="currentDropItem"
				@clickItem="onClickItem"
				@popupChange="onPopupChange">
				
				<!-- 订单状态选项 -->
				<template v-if="currentDropItem.name === 'status'">
					<view class="filter-options">
						<view 
							class="filter-option" 
							v-for="(item, index) in statusOptions" 
							:key="index"
							:class="{ active: statusFilter.value === item.value }"
							@click="selectStatus(item)">
							<text>{{ item.label }}</text>
							<uv-icon v-if="statusFilter.value === item.value" name="checkmark" size="15" color="#ff6b35"></uv-icon>
						</view>
					</view>
				</template>
				
				<!-- 彩种选项 -->
				<template v-if="currentDropItem.name === 'lottery'">
					<view class="filter-options">
						<view 
							class="filter-option" 
							v-for="(item, index) in lotteryOptions" 
							:key="index"
							:class="{ active: lotteryFilter.value === item.type_code }"
							@click="selectLottery(item)">
							<text>{{ item.type_name }}</text>
							<uv-icon v-if="lotteryFilter.value === item.type_code" name="checkmark" size="15" color="#ff6b35"></uv-icon>
						</view>
					</view>
				</template>
				
			</uv-drop-down-popup>
		</view>
		
		<view class="scroll-container">
			
			<!-- 订单列表 -->
			<view class="order-item" v-for="(order, index) in orderList" :key="index" @click="goToDetail(order)">
				<view class="order-number-row">
					<text class="order-number">订单号：{{ order.orderNo }}</text>
				</view>
				<!-- 订单头部 -->
				<view class="order-header">
					<view class="lottery-info">
						<!-- <view class="lottery-icon">
							<image v-if="order.typeicon" :src="order.typeicon" mode="aspectFill" class="type-icon"></image>
							<uv-icon v-else name="list" size="24" color="#ff6b35"></uv-icon>
						</view> -->
						<view class="lottery-details">
							<text class="lottery-name">{{ order.typename }}</text>
							<text class="period-no">第{{ order.periodNo }}期</text>
						</view>
					</view>
					<view class="order-status" :class="order.statusClass">
						<text>{{ order.status }}</text>
					</view>
				</view>
				<!-- 投注内容 -->
				<view class="bet-info">
					<view class="bet-details">
						<view class="bet-info-row">
							<view class="bet-type">
								{{ formatOrderBetContent(order.bet_content) }} 
								<text class="bet-numbers">{{ order.betTypeName }}</text>
							</view>
						</view>
					</view>
					<view class="bet-amount">
						<text class="amount">¥{{ order.bet_amount }}</text>
						<text class="multiple" v-if="order.multiple > 1">×{{ order.multiple }}</text>
					</view>
				</view>
				
				<!-- 底部信息 -->
				<view class="order-footer">
					<view class="member-info">
						<text class="member-nickname">用户：{{ order.nickname }}</text>
						<text class="order-time">{{ order.createTime }}</text>
					</view>
					<view class="result-info">
						<text class="win-amount" v-if="order.win_amount > 0">+¥{{ order.win_amount }}</text>
						<uv-icon name="arrow-right" size="14" color="#999"></uv-icon>
					</view>
				</view>
			</view>
			
			<!-- 空状态 -->
			<uv-empty v-if="orderList.length === 0 && !loading" 
				text="暂无订单数据" 
				icon="/static/images/empty-state.svg"
				textColor="#bbb"
				iconSize="120">
			</uv-empty>
			
			<!-- 加载更多提示 -->
			<view v-if="orderList.length > 0" class="load-more">
				<view v-if="loading" class="loading-text">
					<uv-loading-icon mode="spinner" size="16"></uv-loading-icon>
				</view>
				<view v-else-if="!hasMore" class="no-more-text">
					<text>没有更多数据了</text>
				</view>
				<view v-else class="pull-up-text">
					<text>上拉加载更多</text>
				</view>
			</view>
			
		</view>

		<!-- 订单详情弹窗 -->
		<uv-popup 
			ref="detailPopup"
			mode="bottom" 
			border-radius="50"
			:safe-area-inset-bottom="true"
			custom-style="background-color: #fff; max-height: 85vh;"
			@change="onPopupChange">
			
			<view class="popup-header">
				<text class="popup-title">订单详情</text>
				<uv-icon name="close" size="24" color="#999" @click="closeDetailPopup"></uv-icon>
			</view>
			
			<scroll-view 
				class="popup-content" 
				scroll-y="true"
				@touchstart="onPopupTouchStart"
				@touchmove="onPopupTouchMove"
				@touchend="onPopupTouchEnd"
				@scroll="onPopupScroll">
				<!-- 订单状态卡片 -->
				<view class="status-card" v-if="selectedOrder.id">
					<view class="status-header">
						<view class="status-icon" :class="selectedOrder.statusClass">
							<uv-icon :name="getStatusIcon(selectedOrder.status)" size="28" color="#fff"></uv-icon>
						</view>
						<view class="status-info">
							<text class="status-text">{{ selectedOrder.status }}</text>
							<text class="status-desc">{{ getStatusDesc(selectedOrder.status) }}</text>
						</view>
					</view>
					
					<!-- 中奖金额显示 -->
					<view class="win-amount-display" v-if="selectedOrder.win_amount > 0">
						<text class="win-label">中奖金额</text>
						<text class="win-amount">¥{{ selectedOrder.win_amount }}</text>
					</view>
				</view>
				
				<!-- 彩票信息 -->
				<view class="info-card" v-if="selectedOrder.id">
					<view class="card-title">
						<uv-icon name="list" size="18" color="#ff6b35"></uv-icon>
						<text>订单信息</text>
					</view>
					<view class="info-item">
						<text class="label">订单号：{{ selectedOrder.order_no }}</text>
						<view class="copy-btn" @click="copyOrderNo">
							复制
						</view>
					</view>
					<view class="info-item">
						<text class="label">下单时间</text>
						<text class="value">{{ selectedOrder.createTime }}</text>
					</view>
					<view class="info-item" v-if="selectedOrder.remark">
						<text class="label">备注</text>
						<text class="value">{{ selectedOrder.remark }}</text>
					</view>
				</view>
				
				<!-- 投注信息 -->
				<view class="info-card" v-if="selectedOrder.id">
					<view class="card-title">
						<uv-icon name="list" size="18" color="#ff6b35"></uv-icon>
						<text>投注信息</text>
					</view>
					<view class="info-item">
						<text class="label">彩种</text>
						<text class="value">{{ selectedOrder.typename }}</text>
					</view>
					<view class="info-item">
						<text class="label">期号</text>
						<text class="value">第{{ selectedOrder.periodNo }}期</text>
					</view>
					<view class="info-item">
						<text class="label">玩法</text>
						<text class="value">{{ selectedOrder.betTypeName }}</text>
					</view>
					<view class="bet-content-display">
						<text class="bet-label">投注内容</text>
						<view class="bet-numbers-popup">
							<text class="bet-content-text">{{ formatOrderBetContent(selectedOrder.bet_content) }}</text>
						</view>
					</view>
					<view class="info-item">
						<text class="label">投注金额</text>
						<text class="value amount">¥{{ selectedOrder.bet_amount }}</text>
					</view>
					<view class="info-item">
						<text class="label">投注倍数</text>
						<text class="value">{{ selectedOrder.multiple || 1 }}倍</text>
					</view>
					<view class="info-item">
						<text class="label">总金额</text>
						<text class="value amount">¥{{ selectedOrder.total_amount || selectedOrder.bet_amount }}</text>
					</view>
					<view class="info-item" v-if="selectedOrder.odds">
						<text class="label">赔率</text>
						<text class="value odds">{{ selectedOrder.odds }}</text>
					</view>
				</view>
				
				<!-- 开奖信息 -->
				<view class="info-card" v-if="selectedOrder.drawResult && selectedOrder.drawResult.length > 0">
					<view class="card-title">
						<uv-icon name="list" size="18" color="#ff6b35"></uv-icon>
						<text>开奖信息</text>
					</view>
					<view class="draw-content">
						<text class="draw-label">开奖号码</text>
						<view class="draw-numbers">
							<text class="draw-number" v-for="(num, index) in selectedOrder.drawResult" :key="index">{{ num }}</text>
						</view>
					</view>
					<view class="info-item" v-if="selectedOrder.draw_time">
						<text class="label">开奖时间</text>
						<text class="value">{{ selectedOrder.draw_time }}</text>
					</view>
					<view class="info-item" v-if="selectedOrder.settle_time">
						<text class="label">结算时间</text>
						<text class="value">{{ selectedOrder.settle_time }}</text>
					</view>
				</view>
				
				<!-- 佣金信息 -->
				<view class="info-card">
					<view class="card-title">
						<uv-icon name="list" size="18" color="#ff6b35"></uv-icon>
						<text>佣金信息</text>
					</view>
					<view class="info-item">
						<text class="label">佣金金额</text>
						<text class="value amount">¥{{ selectedOrder.commission_amount }}</text>
					</view>
				</view>
			</scroll-view>
		</uv-popup>

		<!-- 搜索弹窗 -->
		<uv-popup 
			ref="searchPopup"
			:show="showSearchPopup"
			mode="center" 
			border-radius="20"
			custom-style="background-color: #fff; width: 80%; max-width: 400px;"
			@change="onSearchPopupChange">
			
			<view class="search-popup">
				<view class="search-header">
					<text class="search-title">搜索订单号</text>
					<text class="search-title" @click="resetOrder" style="color:#d2d2d2">重置搜索</text>
					<uv-icon name="close" size="24" color="#999" @click="closeSearchPopup"></uv-icon>
				</view>
				
				<view class="search-content">
					<uv-search placeholder="请输入订单号|期号" bgColor="#f5f5f5" :inputStyle="{color:'#333'}" :actionStyle="{color:'#ff6b35'}" v-model="searchKeyword" actionText="搜索" @custom="searchOrder" @search="searchOrder"></uv-search>
				</view>
			</view>
		</uv-popup>

	</view>
</template>

<script>
import authMixin from '@/mixins/auth.js';
import { getMemberOrders } from '@/api/agent.js';
import { getLotteryTypes } from '@/api/lottery.js';
import { formatOrderBetContent } from '@/api/order.js';
	
	export default {
		mixins: [authMixin],
		onPageScroll() {
			// 滚动后及时更新位置
			// 注意：uv-drop-down组件没有ref，这里不需要调用init方法
		},
		data() {
			return {
				// 筛选相关
				currentDropItem: {
					name: '',
					activeIndex: 0,
					child: []
				},
				statusFilter: {
					label: '全部订单',
					value: 0
				},
				lotteryFilter: {
					label: '全部彩种',
					value: 'all'
				},
				statusOptions: [
					{ label: '全部订单', value: 0 },
					{ label: '待开奖', value: 'CONFIRMED' },
					{ label: '已中奖', value: 'WINNING' },
					{ label: '未中奖', value: 'LOSING' }
				],
				lotteryOptions: [
				{ type_name: '全部彩种', type_code: 'all' }
			],
				
				// 搜索相关
				searchKeyword: '',
				showSearchPopup: false,
				
				current: 0,
				refreshing: false, // 刷新状态
				// 触摸滑动相关
				touchStartX: 0,
				touchStartY: 0,
				minSwipeDistance: 50, // 最小滑动距离
				lastSwitchTime: 0, // 上次切换时间，用于防抖
				switchDelay: 300, // 切换防抖延迟（毫秒）

				orderList: [], // 订单列表，通过API动态获取
			currentPage: 1, // 当前页码
			totalPages: 1, // 总页数
			loading: false, // 加载状态
			hasMore: true, // 是否还有更多数据
			
			// 弹窗相关
			selectedOrder: {}, // 选中的订单
			
			// 弹窗滑动关闭相关
			popupTouchStartY: 0,
			popupScrollTop: 0,
			popupCanClose: false
			}
		},
		

		
		onLoad() {
			// 页面加载时获取订单数据
			this.loadOrderData();
			// 获取彩种数据
			this.loadLotteryTypes();
		},
		
		// 下拉刷新
		onPullDownRefresh() {
			// 下拉刷新
			this.onRefresh();
		},
		onReachBottom() {
			// 上拉加载更多
			this.loadMore();
		},
		
		methods: {
				// 格式化投注内容显示
				formatOrderBetContent,

			scrollToTop() {
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 300
				});
			},
			// 处理订单数据映射和状态显示
			processOrderData(orders) {
				return orders.map(order => {
					// 映射字段
			order.orderNo = order.order_no;
			order.memberUsername = order.member_username;
			order.memberNickname = order.member_nickname;
			order.nickname = order.nickname; // 添加昵称字段映射
			order.periodNo = order.period_no;
			order.betTypeName = order.bet_type_name;
			order.drawResult = order.draw_result || [];
			order.price = order.bet_amount;
			order.jiang_price = order.win_amount;
			order.odds = order.odds;
			order.createTime = this.formatTime(order.create_time);
			order.productName = `${order.lottery_code} - ${order.bet_type_name}`;
			order.productImage = '/static/images/202.png';
			// 添加彩种图标
			order.typeicon = order.typeicon || '';
					
					// 处理状态显示
					switch(order.status) {
						case 'PENDING':
							order.status = '待开奖';
							order.statusClass = 'status-pending';
							break;
						case 'WINNING':
							order.status = '待派奖';
							order.statusClass = 'status-winning';
							break;
						case 'PAID':
							order.status = '已派奖';
							order.statusClass = 'status-paid';
							break;
						case 'LOSING':
							order.status = '未中奖';
							order.statusClass = 'status-lost';
							break;
						case 'CANCELLED':
							order.status = '已取消';
							order.statusClass = 'status-cancelled';
							break;
						default:
							order.status = order.status_text || '未知';
							order.statusClass = 'status-default';
					}
					return order;
				});
			},
			
			// 统一的获取订单数据方法
			async fetchOrderData(page = 1, isLoadMore = false) {
				if (this.loading) return;
				
				this.loading = true;
				try {
					// 构建请求参数
					const params = {
						page: page,
						limit: 8
					};
					
					// 添加状态筛选
					if (this.statusFilter.value && this.statusFilter.value !== 0) {
						params.status = this.statusFilter.value;
					}
					
					// 添加彩种筛选
					if (this.lotteryFilter.value && this.lotteryFilter.value !== 'all') {
						params.lottery_code = this.lotteryFilter.value;
					}
					
					// 添加搜索关键词
					if (this.searchKeyword.trim()) {
						params.keyword = this.searchKeyword.trim();
					}
					
					const response = await getMemberOrders(params);
					
					if (response.code === 1) {
						const orders = response.data.data || [];
						const processedOrders = this.processOrderData(orders);
						
						if (isLoadMore) {
							// 滚动加载：追加数据
							this.orderList = [...this.orderList, ...processedOrders];
							this.currentPage++;
						} else {
							// 页面加载：重置数据
							this.orderList = processedOrders;
							this.currentPage = 1;
						}
						
						this.totalPages = Math.ceil((response.data.total || 0) / 20);
						this.hasMore = this.currentPage < this.totalPages;
					} else {
						uni.showToast({
							title: response.msg || '获取订单失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('获取订单失败:', error);
					uni.showToast({
						title: '网络错误',
						icon: 'none'
					});
				} finally {
					this.loading = false;
				}
			},
			
			// 加载订单数据
			async loadOrderData() {
				await this.fetchOrderData(1, false);
			},
			
			// 加载彩种类型数据
			async loadLotteryTypes() {
				try {
					const response = await getLotteryTypes();
					if (response.code === 1) {
						const lotteryTypes = response.data || [];
						// 保留"全部彩种"选项，然后添加API返回的彩种
						this.lotteryOptions = [
							{ type_name: '全部彩种', type_code: 'all' },
							...lotteryTypes.map(item => ({
								type_name: item.type_name,
								type_code: item.type_code
							}))
						];
					}
				} catch (error) {
					console.error('获取彩种类型失败:', error);
				}
			},
			
			// 复制订单号
			copyOrderNo(orderNo) {
				// 阻止事件冒泡
				event.stopPropagation();
				
				uni.setClipboardData({
					data: orderNo,
					success: () => {
						uni.showToast({
							title: '订单号已复制',
							icon: 'success'
						});
					},
					fail: () => {
						uni.showToast({
							title: '复制失败',
							icon: 'none'
						});
					}
				});
			},
			// 加载更多数据
			async loadMore() {
				if (this.loading || !this.hasMore) return;
				await this.fetchOrderData(this.currentPage + 1, true);
			},
			
			// scroll-view刷新事件
			onRefresh() {
				this.refreshing = true;
				this.loadOrderData().then(() => {
					this.refreshing = false;
					uni.stopPullDownRefresh();
				});
			},
			
			// 触摸开始事件
			onTouchStart(e) {
				this.touchStartX = e.touches[0].clientX;
				this.touchStartY = e.touches[0].clientY;
			},
			
			// 触摸结束事件
			onTouchEnd(e) {
				const currentTime = Date.now();
				
				// 防抖处理
				if (currentTime - this.lastSwitchTime < this.switchDelay) {
					return;
				}
				
				const touchEndX = e.changedTouches[0].clientX;
				const touchEndY = e.changedTouches[0].clientY;
				
				const deltaX = touchEndX - this.touchStartX;
				const deltaY = touchEndY - this.touchStartY;
				
				// 判断是否为水平滑动（水平滑动距离大于垂直滑动距离）
				if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > this.minSwipeDistance) {
					this.lastSwitchTime = currentTime;
					
					if (deltaX > 0) {
						// 向右滑动，切换到上一个标签
						this.switchToPrevTab();
					} else {
						// 向左滑动，切换到下一个标签
						this.switchToNextTab();
					}
				}
			},
			
			// 切换到上一个标签
			switchToPrevTab() {
				if (this.current > 0) {
					this.current--;
					this.scrollToTop(); // 滚动到顶部
					this.loadOrderData(); // 切换标签时重新加载数据
				}
			},
			
			// 切换到下一个标签
			switchToNextTab() {
				if (this.current < this.list.length - 1) {
					this.current++;
					this.scrollToTop(); // 滚动到顶部
					this.loadOrderData(); // 切换标签时重新加载数据
				}
			},
			
			// 显示订单详情弹窗
			goToDetail(order) {
				this.selectedOrder = order;
				this.$refs.detailPopup.open();
			},
			
			// 关闭详情弹窗
			closeDetailPopup() {
				this.$refs.detailPopup.close();
				this.selectedOrder = {};
			},
			
			// 弹窗状态变化事件
			onPopupChange(e) {
				if (!e.show) {
					// 弹窗关闭时清空选中订单
					this.selectedOrder = {};
				}
			},
			
			// 获取状态图标
			getStatusIcon(status) {
				switch(status) {
					case '待开奖':
						return 'clock';
					case '已中奖':
						return 'checkmark-circle';
					case '未中奖':
						return 'close-circle';
					case '已取消':
						return 'close';
					case '已退款':
						return 'reload';
					default:
						return 'list';
				}
			},
			
			// 获取状态描述
			getStatusDesc(status) {
				switch(status) {
					case '待开奖':
						return '等待开奖结果';
					case '已中奖':
						return '恭喜中奖';
					case '未中奖':
						return '很遗憾未中奖';
					case '已取消':
						return '订单已取消';
					case '已退款':
						return '订单已退款';
					default:
						return '';
				}
			},
			

			
			// 复制订单号
			copyOrderNo() {
				uni.setClipboardData({
					data: this.selectedOrder.order_no,
					success: () => {
						uni.showToast({
							title: '订单号已复制',
							icon: 'success'
						});
					}
				});
			},
			
			// 格式化时间
			formatTime(timestamp) {
				if (!timestamp) return '';
				const date = new Date(timestamp * 1000); // 时间戳转换为毫秒
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				const hours = String(date.getHours()).padStart(2, '0');
				const minutes = String(date.getMinutes()).padStart(2, '0');
				return `${year}-${month}-${day} ${hours}:${minutes}`;
			},
			
			// 弹窗滑动关闭相关方法
			onPopupTouchStart(e) {
				this.popupTouchStartY = e.touches[0].clientY;
			},
			
			onPopupTouchMove(e) {
				const currentY = e.touches[0].clientY;
				const deltaY = currentY - this.popupTouchStartY;
				
				// 只有在滚动到顶部且向下滑动时才允许关闭
				if (this.popupScrollTop <= 0 && deltaY > 0) {
					this.popupCanClose = true;
				} else {
					this.popupCanClose = false;
				}
			},
			
			onPopupTouchEnd(e) {
				const currentY = e.changedTouches[0].clientY;
				const deltaY = currentY - this.popupTouchStartY;
				
				// 如果滑动距离大于50px且允许关闭，则关闭弹窗
				if (this.popupCanClose && deltaY > 50) {
					this.closeDetailPopup();
				}
				
				// 重置状态
				this.popupCanClose = false;
			},
			
			onPopupScroll(e) {
				this.popupScrollTop = e.detail.scrollTop;
			},
			
			// 处理搜索按钮点击事件
			handleSearch() {
				this.$refs.searchPopup.open();
			},
			
			// 筛选菜单选择事件
			onSelectMenu(e) {
				console.log('筛选菜单点击:', e);
				
				if (e.name === 'search' && e.active) {
					// 点击搜索项时显示搜索弹窗
					this.$refs.searchPopup.open();
					return;
				}
				
				// 设置当前下拉项数据
				if (e.name === 'status') {
					this.currentDropItem = {
						name: 'status',
						activeIndex: this.statusOptions.findIndex(item => item.value === this.statusFilter.value),
						child: this.statusOptions
					};
				} else if (e.name === 'lottery') {
					this.currentDropItem = {
						name: 'lottery',
						activeIndex: this.lotteryOptions.findIndex(item => item.type_code === this.lotteryFilter.value),
						child: this.lotteryOptions
					};
				}
			},
			
			// 筛选项点击事件
			onClickItem(item) {
				console.log('筛选项点击:', item);
				
				if (this.currentDropItem.name === 'status') {
					this.selectStatus(item);
				} else if (this.currentDropItem.name === 'lottery') {
					this.selectLottery(item);
				}
			},
			
			// 筛选弹窗状态变化
			onPopupChange(e) {
				console.log('筛选弹窗状态:', e);
			},
			
			// 选择订单状态
			selectStatus(item) {
				this.statusFilter = {
					label: item.label,
					value: item.value
				};
				// 滚动到顶部
				this.scrollToTop();
				// 重新加载数据
				this.loadOrderData();
			},
			
			// 选择彩种
			selectLottery(item) {
				this.lotteryFilter = {
					label: item.type_name,
					value: item.type_code
				};
				// 滚动到顶部
				this.scrollToTop();
				// 重新加载数据
				this.loadOrderData();
			},
			
			// 执行搜索
			searchOrder() {
				if (!this.searchKeyword.trim()) {
					uni.showToast({
						title: '请输入订单号|期号',
						icon: 'none'
					});
					return;
				}
				this.$refs.searchPopup.close();
				// 释放搜索触发状态
				this.showSearchPopup = false;
				// 滚动到顶部
				this.scrollToTop();
				// 重新加载数据
				this.loadOrderData();
			},
			
			// 重置搜索
			resetOrder() {
				this.searchKeyword = '';
				this.$refs.searchPopup.close();
				// 释放搜索触发状态
				this.showSearchPopup = false;
				// 滚动到顶部
				this.scrollToTop();
				// 重新加载数据
				this.loadOrderData();
			},
			
			// 关闭搜索弹窗
			closeSearchPopup(e) {
				this.$refs.searchPopup.close();
			},
			
			// 搜索弹窗状态变化
			onSearchPopupChange(e) {
				this.showSearchPopup = e.show;
			}
		},
		mounted() {
			this.loadOrderData();
		}
	}
</script>

<style scoped lang="scss">
	.container {
		min-height: 100vh;
		background-color: #f8f9fa;
		color: #333;
		position: relative;
	}

	.scroll-container {
		top: 98rpx;
		padding: 16rpx;
		box-sizing: border-box;
		overflow-y: auto;
		-webkit-overflow-scrolling: touch;
		background-color: #f8f9fa;
		position: relative;
	}
	
	.order-item {
		background: #fff;
		margin-bottom: 15rpx;
		border-radius: 45rpx 0 45rpx 0;
		padding: 24rpx;
		border: 1px solid #e9ecef;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		position: relative;
		transition: all 0.2s ease;
		cursor: pointer;
		overflow: hidden;
	}
	
	.order-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20rpx;
	}
	
	.lottery-info {
		display: flex;
		align-items: center;
		gap: 16rpx;
	}
	
	.lottery-icon {
		width: 55rpx;
		height: 55rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		box-shadow: 0 2rpx 8rpx rgba(255, 107, 53, 0.2);
		position: relative;
		overflow: hidden;
	}
	
	.type-icon {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		object-fit: cover;
	}
	
	.lottery-code {
		font-size: 20rpx;
		color: #fff;
		font-weight: 600;
		letter-spacing: 0.5rpx;
	}
	
	.lottery-details {
		display: flex;
		flex-direction: column;
		gap: 4rpx;
	}
	
	.lottery-name {
		font-size: 28rpx;
		color: orangered;
		font-weight: 600;
	}
	
	.period-no {
		font-size: 23rpx;
		color: #666;
		font-weight: 400;
	}
	
	.order-status {
		padding: 6rpx 16rpx;
		border-radius: 12rpx;
		font-size: 22rpx;
		font-weight: 500;
		box-shadow: 0 2rpx 6rpx rgba(0, 0, 0, 0.1);
		letter-spacing: 0.3rpx;
		position: relative;
		transition: all 0.2s ease;
	}
	
	.status-pending {
		background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 193, 7, 0.05));
		color: #ffc107;
		border: 1px solid rgba(255, 193, 7, 0.3);
	}
	
	.status-winning {
		background: linear-gradient(135deg, rgba(40, 167, 69, 0.15), rgba(40, 167, 69, 0.05));
		color: #28a745;
		border: 1px solid rgba(40, 167, 69, 0.3);
	}
	
	.status-paid {
		background: linear-gradient(135deg, rgba(23, 162, 184, 0.15), rgba(23, 162, 184, 0.05));
		color: #17a2b8;
		border: 1px solid rgba(23, 162, 184, 0.3);
	}
	
	.status-lost {
		background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(108, 117, 125, 0.05));
		color: #6c757d;
		border: 1px solid rgba(108, 117, 125, 0.3);
	}
	
	.status-cancelled {
		background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.05));
		color: #dc3545;
		border: 1px solid rgba(220, 53, 69, 0.3);
	}
	
	.status-default {
		background: linear-gradient(135deg, rgba(108, 117, 125, 0.15), rgba(108, 117, 125, 0.05));
		color: #6c757d;
		border: 1px solid rgba(108, 117, 125, 0.3);
	}
	
	.bet-info {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 16rpx;
		padding: 12rpx 0;
		border-bottom: 1px solid #e9ecef;
	}
	
	.bet-details {
		display: flex;
		flex-direction: column;
		gap: 8rpx;
		flex: 1;
	}
	
	.bet-type {
		font-size: 25rpx;
		color: #ff6b35;
		font-weight: 500;
		margin-bottom: 8rpx;
		letter-spacing: 0.3rpx;
	}
	
	.bet-numbers {
		font-size: 26rpx;
		color: #999;
		font-weight: 400;
		margin-left:15rpx;
		letter-spacing: 0.2rpx;
		max-width: 300rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	
	.order-number-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 15rpx;
	}
	
	.order-number {
		font-size: 25rpx;
		color: #666;
		font-weight: 500;
		flex: 1;
	}
	
	.copy-btn {
		padding: 8rpx 12rpx;
		background-color: #f8f9fa;
		border: 1px solid #e9ecef;
		border-radius: 8rpx;
		margin-left: 20rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 60rpx;
		height: 40rpx;
		transition: all 0.2s ease;
		color: #666;
		
		&:active {
			background-color: #e9ecef;
			transform: scale(0.95);
		}
	}
	
	.bet-info-row {
		display: flex;
		flex-direction: column;
		gap: 5rpx;
	}
	
	.bet-amount {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}
	
	.amount {
		font-size: 25rpx;
		color: #28a745;
		font-weight: 600;
	}
	
	.multiple {
		font-size: 20rpx;
		color: #333;
		background: #ff6b35;
		padding: 4rpx 12rpx;
		border-radius: 8rpx;
		box-shadow: 0 2rpx 4rpx rgba(255, 107, 53, 0.2);
		font-weight: 500;
		letter-spacing: 0.3rpx;
	}
	
	.order-footer {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding-top: 16rpx;
	}
	
	.member-info {
		display: flex;
		flex-direction: column;
		gap: 6rpx;
	}
	
	.member-nickname {
		font-size: 28rpx;
		color: #747474;
		font-weight: 500;
	}
	
	.order-time {
		font-size: 26rpx;
		color: #999;
		font-weight: 400;
	}
	
	.result-info {
		display: flex;
		align-items: center;
		gap: 12rpx;
	}
	
	.win-amount {
		font-size: 26rpx;
		color: #ff3d23;
		font-weight: 600;
		letter-spacing: 0.3rpx;
	}
	

	
	.load-more {
		padding: 40rpx 20rpx;
		text-align: center;
		margin: 20rpx 0;
	}
	
	.no-more-text {
		font-size: 28rpx;
		color: #bbb;
		padding: 12rpx 20rpx;
		border-radius: 8rpx;
		font-weight: 500;
	}
	
	.pull-up-text {
		font-size: 28rpx;
		color: #bbb;
		padding: 12rpx 20rpx;
		background: rgba(255, 255, 255, 0.05);
		border-radius: 8rpx;
		border: 1px solid rgba(255, 255, 255, 0.1);
		font-weight: 400;
	}
	
	// 弹窗样式
	.popup-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 24rpx 32rpx;
		border-bottom: 1px solid rgba(255, 107, 53, 0.15);
		background: #fff;
	}
	
	.popup-title {
		font-size: 30rpx;
		color: #333;
		font-weight: 600;
		text-shadow: 0 1rpx 2rpx rgba(0, 0, 0, 0.1);
	}
	
	.popup-content {
		max-height: 70vh;
		width:96%;
		padding:15rpx 2%;
		background: #fff;
	}
	
	// 状态卡片样式
	.status-card {
		background: rgba(255, 107, 53, 0.1);
		border-radius: 16rpx;
		padding: 20rpx;
		margin-bottom: 16rpx;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		border: 1px solid rgba(255, 107, 53, 0.2);
		position: relative;
	}
	
	.status-header {
		display: flex;
		align-items: center;
		gap: 16rpx;
		margin-bottom: 20rpx;
	}
	
	.status-icon {
		width: 40rpx;
		height: 40rpx;
		border-radius: 8rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: rgba(255, 107, 53, 0.2);
	}
	
	.status-info {
		flex: 1;
	}
	
	.status-text {
		font-size: 30rpx;
		color: #ff6b35;
		font-weight: 600;
		display: block;
		margin-bottom: 4rpx;
	}
	
	.status-desc {
		font-size: 22rpx;
		color: #999;
		font-weight: 400;
	}
	
	.win-amount-display {
		text-align: center;
		padding: 15rpx;
		background: rgba(40, 167, 69, 0.1);
		border-radius:55rpx;
	}
	
	.win-label {
		font-size: 24rpx;
		color: #999;
		display: block;
		margin-bottom: 6rpx;
	}
	
	// 信息卡片样式
	.info-card {
		background: rgba(255, 255, 255, 0.02);
		border-radius: 12rpx;
		padding: 16rpx;
		margin-bottom: 22rpx;
		border: 1px solid rgba(255, 255, 255, 0.08);
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
		position: relative;
	}
	
	.card-title {
		display: flex;
		align-items: center;
		gap: 8rpx;
		margin-bottom: 16rpx;
		padding-bottom: 8rpx;
		border-bottom: 1px solid rgba(255, 255, 255, 0.08);
		
		text {
			font-size: 28rpx;
			color: #333;
			font-weight: 500;
		}
	}
	
	.info-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 10rpx 0;
		border-bottom: 1px solid rgba(255, 255, 255, 0.05);
		
		&:last-child {
			border-bottom: none;
		}
	}
	
	.label {
		font-size: 26rpx;
		color: #bbb;
		font-weight: 500;
		min-width: 100rpx;
	}
	
	.value {
		font-size: 26rpx;
		color: #666;
		font-weight: 500;
		flex: 1;
		text-align: right;
		
		&.amount {
			color: #28a745;
			font-weight: 600;
		}
		
		&.odds {
			color: #ff6b35;
			font-weight: 600;
		}
		
		&.order-no {
			font-size: 25rpx;
			max-width: 240rpx;
			word-break: break-all;
		}
	}
	
	.copy-btn {
		font-size: 20rpx;
		padding: 4rpx 10rpx;
		margin-left: 8rpx;
		border-radius: 6rpx;
		background: rgba(255, 107, 53, 0.1);
		border: 1px solid rgba(255, 107, 53, 0.2);
		transition: all 0.2s ease;
		color: #ff6b35;
		
		&:active {
			background: rgba(255, 107, 53, 0.2);
			transform: scale(0.95);
		}
	}
	
	.bet-content-display {
		margin-bottom: 16rpx;
	}
	
	.bet-label {
		font-size: 28rpx;
		color: #bbb;
		font-weight: 500;
		margin-bottom: 12rpx;
		display: block;
	}
	
	.bet-numbers-popup {
		display: flex;
		flex-wrap: wrap;
		gap: 8rpx;
	}
	
	.bet-number {
		background: #ff6b35;
		color: #fff;
		border-radius: 6rpx;
		padding: 4rpx 10rpx;
		font-size: 24rpx;
		font-weight: 500;
		box-shadow: 0 1rpx 3rpx rgba(255, 107, 53, 0.2);
	}
	
	.bet-content-text {
		font-size: 26rpx;
		color: #333;
		line-height: 1.4;
		word-break: break-all;
	}
	
	.draw-content {
		margin-bottom: 12rpx;
	}
	
	.draw-label {
		font-size: 26rpx;
		color: #999;
		font-weight: 500;
		margin-bottom: 10rpx;
		display: block;
	}
	
	.draw-numbers {
		display: flex;
		flex-wrap: wrap;
		gap: 6rpx;
	}
	
	.draw-number {
		background: #dc3545;
		color: #fff;
		border-radius: 8rpx;
		width: 44rpx;
		height: 44rpx;
		line-height: 44rpx;
		text-align: center;
		font-size: 22rpx;
		font-weight: 500;
		box-shadow: 0 1rpx 3rpx rgba(220, 53, 69, 0.2);
	}

	// 筛选容器样式
	.filter-container {
		position: fixed;
		top:0;
		/* #ifdef H5 */
		top: 80rpx;
		/* #endif */
		left: 0;
		right: 0;
		z-index: 99;
	}

	// 筛选选项样式
	.filter-options {
		padding: 20rpx;
		background-color: #fff;
	}
	
	.filter-option {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20rpx 15rpx;
		margin-bottom: 12rpx;
		background: #f8f9fa;
		border-radius: 12rpx;
		border: 1px solid #e9ecef;
		transition: all 0.2s ease;
		
		&.active {
			background: rgba(255, 107, 53, 0.1);
			border-color: rgba(255, 107, 53, 0.3);
		}
		
		text {
			font-size: 28rpx;
			color: #333;
			font-weight: 500;
		}
		
		&.active text {
			color: #ff6b35;
		}
	}

	// 搜索弹窗样式
	.search-popup {
		padding: 40rpx;
		background-color: #fff;
		border-radius: 20rpx;
	}
	
	.search-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30rpx;
	}
	
	.search-title {
		font-size: 27rpx;
		color: #333;
		font-weight: 600;
	}

	.search-content {
		display: flex;
		flex-direction: column;
		gap: 30rpx;
	}

	.search-input-wrapper {
		background-color: #f8f9fa;
		border-radius: 12rpx;
		padding: 0 25rpx;
		height: 100rpx;
		border: 2rpx solid #e9ecef;
		transition: border-color 0.3s ease;
		display: flex;
		align-items: center;
		
		&:focus-within {
			border-color: #007AFF;
			background-color: #fff;
		}
	}

	.search-buttons {
		display: flex;
		gap: 20rpx;
		justify-content: space-between;
		
		:deep(.uv-button) {
			height: 88rpx !important;
			border-radius: 12rpx !important;
			font-size: 32rpx !important;
			font-weight: 500 !important;
			flex: 1;
		}
		
		:deep(.uv-button--info) {
			background-color: #f8f9fa !important;
			border: 2rpx solid #e9ecef !important;
			color: #666 !important;
		}
		
		:deep(.uv-button--primary) {
			background: linear-gradient(135deg, #ff6b35 0%, #e55a2b 100%) !important;
			box-shadow: 0 8rpx 20rpx rgba(255, 107, 53, 0.3) !important;
			border: none !important;
		}
	}

	.search-tips {
		margin-top: 16rpx;
	}

	.tip-item {
		display: flex;
		align-items: center;
		gap: 8rpx;
		padding: 12rpx 16rpx;
		background: rgba(255, 255, 255, 0.05);
		border-radius: 8rpx;
		border: 1px solid rgba(255, 255, 255, 0.1);
		transition: all 0.2s ease;

		text {
			font-size: 26rpx;
			color: #999;
		}

		&:active {
			background: rgba(255, 255, 255, 0.1);
			transform: scale(0.98);
		}
	}

</style>
